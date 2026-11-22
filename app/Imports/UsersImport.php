<?php
namespace App\Imports;
use App\Models\Statement;
use App\Models\Rule;
use App\Models\Entity;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Session;
use App\Support\VoucherNumber;
use App\Support\FinancialYears;
use Illuminate\Support\Str;
class UsersImport implements ToModel,WithStartRow,WithLimit
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
   // what the upload did, read by the controller afterwards
   public $saved = 0;
   public $rejected = [];

   public function  __construct($data){
      $this->data = $data;
      $this->voucherNumbers = VoucherNumber::sequence('SBV-',5);
   }
   // counts shown after the upload
   public $auto_categorised = 0;
   public $conflicts = 0;
   public $batch = null;

   // One header row per voucher (Stage 1): new bank lines get one too, so they show up under Vouchers.
   private function createVoucherHeader($voucher_no, $fyear, $date){
      $merchant = Session::get('merchant_id');
      $id = \DB::table('vouchers')->insertGetId([
         'merchant_id'=>$merchant, 'voucher_no'=>$voucher_no, 'series'=>'SBV', 'fyear'=>$fyear,
         'voucher_date'=>$date, 'status'=>'posted', 'version'=>1, 'created_by'=>$merchant,
         'created_at'=>now(), 'updated_at'=>now(),
      ]);
      \DB::table('voucher_versions')->insert([
         'voucher_id'=>$id, 'version'=>1, 'action'=>'created', 'reason'=>'Bank statement upload',
         'changed_by'=>$merchant, 'created_at'=>now(), 'updated_at'=>now(),
      ]);
      return $id;
   }

   // Audit: which rules fitted this line and what happened (nothing is ever deleted).
   private function recordRuleAudit($voucher_no, $fyear, $date, $description, $amount, $direction, $matched, $outcome, $applied_rule, $head_id){
      if($this->batch===null){ $this->batch = date('YmdHis').'-'.Session::get('id'); }
      $ids = array_values(array_unique(array_column($matched,'rule')));
      sort($ids);
      if($outcome=='conflict'){ $this->conflicts++; }
      if($outcome=='auto'){ $this->auto_categorised++; }
      \DB::table('rule_audit')->insert([
         'merchant_id'=>Session::get('merchant_id'), 'batch'=>$this->batch, 'voucher_no'=>$voucher_no, 'fyear'=>$fyear,
         'line_date'=>$date, 'description'=>substr($description,0,500), 'amount'=>(float)$amount, 'direction'=>$direction,
         'matched'=>json_encode($matched), 'matched_key'=>implode(',',$ids), 'outcome'=>$outcome,
         'applied_rule_id'=>$applied_rule, 'head_id'=>($head_id!=="" ? $head_id : null),
         'created_by'=>Session::get('id'), 'created_at'=>now(), 'updated_at'=>now(),
      ]);
   }

   // ---- rule matching helpers (used for rules whose first condition is on the Description) ----
   private function textMatches($mode, $wanted, $text){
      $wanted = trim((string)$wanted);
      if($mode=="Exact"){
         return strcasecmp(trim((string)$text), $wanted)==0;
      }
      // "like", "Any" and the older stored forms all mean: the description contains the text
      if($wanted===""){ return true; }
      return Str::contains(strtoupper((string)$text), strtoupper($wanted));
   }
   private function amountMatches($cond, $wanted, $amount){
      if($cond===null || $cond==="" || $cond==="Any" || $cond==="Any Value"){ return true; }
      $a = (float)$amount; $w = (float)$wanted;
      switch($cond){
         case "Exact": return abs($a-$w) < 0.005;
         case ">=": return $a >= $w;
         case "<=": return $a <= $w;
         case ">":  return $a > $w;
         case "<":  return $a < $w;
      }
      return false;
   }
   public function startRow(): int{
      return $this->data['header_row']+1;
      // if($this->data['header']=="Yes"){
      //    return 2;
      // }else{
      //    return 1;
      // }      
   }
   public function limit(): int
   {
      return $this->data['last_row'];
   }
   public function model(array $row){
      array_unshift($row,"");
      unset($row[0]);
      // Only lines dated inside the current financial year are accepted: a closed year
      // cannot be changed, and a line must belong to the year it is filed under.
      $line_date = $row[$this->data['date_col']] ?? '';
      if(empty($line_date) || strtotime($line_date)===false){
         $this->rejected[] = ['date'=>(string)$line_date,'why'=>'no valid date','remark'=>substr((string)($row[$this->data['description_col']] ?? ''),0,50)];
         return null;
      }
      // The year is worked out from the line's own date (31 March cut-off). A line whose year is closed is held back.
      if(!FinancialYears::acceptsDate($line_date)){
         $this->rejected[] = ['date'=>date('Y-m-d',strtotime($line_date)),'why'=>'belongs to '.FinancialYears::yearForDate($line_date).', which is closed or not open yet','remark'=>substr((string)($row[$this->data['description_col']] ?? ''),0,50)];
         return null;
      }
      $amount_column = $this->data['amount_column'];
      $bank = $this->data['bank'];
      $date_col = $this->data['date_col'];
      $description_col = $this->data['description_col'];
      $amount_col = $this->data['amount_col'];
      $debit_amount_col = $this->data['debit_amount_col'];
      $credit_amount_col = $this->data['credit_amount_col'];
      //$select_rule = $this->data['select_rule'];
      $amount_contain = $this->data['amount_contain'];
      $debit_amount = "";$credit_amount = "";$status = 0;$head_id = "";
      if($amount_column=="One Column"){
         if($amount_contain=="Dr/Cr"){
            if(str_contains($row[$amount_col],'DR')){ 
               $debit_amount = str_replace("DR","",$row[$amount_col]);
            }else if(str_contains($row[$amount_col],'Dr')){
               $debit_amount = str_replace("Dr","",$row[$amount_col]);
            }else if(str_contains($row[$amount_col],'dr')){
               $debit_amount = str_replace("dr","",$row[$amount_col]);
            }else{
               $credit_amount = str_replace("CR","",$row[$amount_col]);
               $credit_amount = str_replace("Cr","",$credit_amount);
               $credit_amount = str_replace("cr","",$credit_amount);
            }            
         }else if($amount_contain=="+/-"){           
            if(str_contains($row[$amount_col],'-')){ 
               $credit_amount = abs($row[$amount_col]);            
            }else{
               $debit_amount = $row[$amount_col];
            }            
         }
      }else{
         $credit_amount = $row[$credit_amount_col];
         $debit_amount = $row[$debit_amount_col];
      }
      $credit_amount = trim($credit_amount);
      $debit_amount = trim($debit_amount);
      //$rule = Rule::where('id',$select_rule)->first();
      $rules = \App\Support\OpenDocuments::reserved($debit_amount, $credit_amount) ? collect() : Rule::select('*')->where('status',1)->orderBy('id')->get();
      $matched_rules = [];

      // The lines are saved whether or not any rule exists; rules (if any) only choose the account.
      {
         foreach ($rules as $k => $rule) {
            $head_id = ""; $status = 0; // judge each rule on its own
            // A rule saved for "Any bank" (empty or ANY) applies to every bank.
            if($rule->rule_in==$bank || $rule->rule_in===null || $rule->rule_in==='' || $rule->rule_in==='ANY'){
               if($rule->col_type=="Amount"){
                  $cond1 = $rule->rule_condition;
                  if($rule->rule_condition=="Exact"){
                     $cond1 = "==";
                  }
                  if($rule->rule_for=="Debit"){
                     switch($cond1){
                        case ">=":
                           if($debit_amount>=$rule->content){
                              if($rule->rule_condition1=="Exact"){
                                 if($row[$description_col]==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="like"){
                                 $content = strtoupper($rule->content1);
                                 $search = strtoupper($row[$description_col]);
                                 if(Str::contains($search, $content)==true){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case "<=":
                           if($debit_amount<=$rule->content){
                              if($rule->rule_condition1=="Exact"){
                                 if($row[$description_col]==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="like"){
                                 $content = strtoupper($rule->content1);
                                 $search = strtoupper($row[$description_col]);
                                 if(Str::contains($search, $content)==true){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "<":
                           if($debit_amount<$rule->content){
                              if($rule->rule_condition1=="Exact"){
                                 if($row[$description_col]==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="like"){
                                 $content = strtoupper($rule->content1);
                                 $search = strtoupper($row[$description_col]);
                                 if(Str::contains($search, $content)==true){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case ">":
                           if($debit_amount>$rule->content){
                              if($rule->rule_condition1=="Exact"){
                                 if($row[$description_col]==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="like"){
                                 $content = strtoupper($rule->content1);
                                 $search = strtoupper($row[$description_col]);
                                 if(Str::contains($search, $content)==true){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "==":
                           if($debit_amount==$rule->content){
                              if($rule->rule_condition1=="Exact"){
                                 if($row[$description_col]==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="like"){
                                 $content = strtoupper($rule->content1);
                                 $search = strtoupper($row[$description_col]);
                                 if(Str::contains($search, $content)==true){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case "Any":                        
                           if($rule->rule_condition1=="Exact"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else if($rule->rule_condition1=="like"){
                              $content = strtoupper($rule->content1);
                              $search = strtoupper($row[$description_col]);
                              if(Str::contains($search, $content)==true){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else if($rule->rule_condition1=="Any"){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }                        
                        break;
                     }           
                  }else if($rule->rule_for=="Credit"){
                     switch($cond1){
                        case ">=":
                           if($credit_amount>=$rule->content){
                              if($rule->rule_condition1=="Exact"){
                                 if($row[$description_col]==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="like"){
                                 $content = strtoupper($rule->content1);
                                 $search = strtoupper($row[$description_col]);
                                 if(Str::contains($search, $content)==true){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case "<=":
                           if($credit_amount<=$rule->content){
                              if($rule->rule_condition1=="Exact"){
                                 if($row[$description_col]==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="like"){
                                 $content = strtoupper($rule->content1);
                                 $search = strtoupper($row[$description_col]);
                                 if(Str::contains($search, $content)==true){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "<":
                           if($credit_amount<$rule->content){
                              if($rule->rule_condition1=="Exact"){
                                 if($row[$description_col]==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="like"){
                                 $content = strtoupper($rule->content1);
                                 $search = strtoupper($row[$description_col]);
                                 if(Str::contains($search, $content)==true){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case ">":
                           if($credit_amount>$rule->content){
                              if($rule->rule_condition1=="Exact"){
                                 if($row[$description_col]==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="like"){
                                 $content = strtoupper($rule->content1);
                                 $search = strtoupper($row[$description_col]);
                                 if(Str::contains($search, $content)==true){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "==":
                           if($credit_amount==$rule->content){
                              if($rule->rule_condition1=="Exact"){
                                 if($row[$description_col]==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="like"){
                                 $content = strtoupper($rule->content1);
                                 $search = strtoupper($row[$description_col]);
                                 if(Str::contains($search, $content)==true){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                        break;
                        case "Any":                        
                           if($rule->rule_condition1=="Exact"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else if($rule->rule_condition1=="like"){
                              $content = strtoupper($rule->content1);
                              $search = strtoupper($row[$description_col]);
                              if(Str::contains($search, $content)==true){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else if($rule->rule_condition1=="Any"){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }                         
                        break;
                     }
                  }
               }else if($rule->col_type=="Description"){
                  // Rule that starts on the Description: text first, then (optionally) the amount.
                  $line_is_debit  = $debit_amount !== "" && (float)$debit_amount > 0;
                  $line_is_credit = $credit_amount !== "" && (float)$credit_amount > 0;
                  $direction_ok = ($rule->rule_for=="Debit" && $line_is_debit) || ($rule->rule_for=="Credit" && $line_is_credit);
                  if($direction_ok && $this->textMatches($rule->rule_condition, $rule->content, $row[$description_col])){
                     $line_amount = $rule->rule_for=="Debit" ? $debit_amount : $credit_amount;
                     if($this->amountMatches($rule->rule_condition1, $rule->content1, $line_amount)){
                        $status = 1;
                        $head_id = $rule->head_id;
                     }
                  }
               }
               // Do not stop at the first match: every rule is tested so that clashes can be shown.
               if(!empty($head_id)){
                  $matched_rules[] = ['rule'=>$rule->id,'head'=>$head_id];
               }
            }
         }
         // Outcome: no match, one account, or a clash between rules pointing to different accounts.
         $outcome = 'none'; $applied_rule = null; $head_id = ""; $status = 0;
         if(count($matched_rules)>0){
            $distinct_heads = array_values(array_unique(array_column($matched_rules,'head')));
            if(count($distinct_heads)==1){
               $outcome = 'auto';
               $applied_rule = $matched_rules[0]['rule'];
               $head_id = $distinct_heads[0];
               $status = 1;
            }else{
               $outcome = 'conflict';
            }
         }
         $entity = Entity::where('status',1)->where('merchant_id',Session::get('merchant_id'))->orderBy('id','desc')->first();
         $fyear = FinancialYears::yearForDate($line_date);
         // The bank's own entry is created for EVERY line, exactly as the bank shows it
         // (money in = debit on the bank, money out = credit), whether or not a rule matched.
         // Categorising later only chooses the account for the other, reversed entry.
         // one System Bank Voucher number per bank line, shared by both of its entries
         $voucher_no = $this->voucherNumbers->next();
         $line_date_db = date('Y-m-d',strtotime($row[$date_col]));
         $voucher_id = $this->createVoucherHeader($voucher_no, $fyear, $line_date_db);
         $this->recordRuleAudit($voucher_no, $fyear, $line_date_db, (string)$row[$description_col],
                                $debit_amount !== "" ? $debit_amount : $credit_amount,
                                $debit_amount !== "" ? 'Debit' : 'Credit', $matched_rules, $outcome, $applied_rule, $head_id);
         if(!empty($bank)){
            $statement = new Statement;
            $statement->voucher_id = $voucher_id;
            $statement->voucher_no = $voucher_no;
            $statement->head_id = $bank;
            $statement->debit = $credit_amount;
            $statement->credit = $debit_amount;
            $statement->bank_id = $bank;
            $statement->fyear = $fyear;
            $statement->remark = $row[$description_col];
            $statement->st_date = date('Y-m-d',strtotime($row[$date_col]));
            $statement->status = $status;   // both entries of a line count, or neither does
            $statement->merchant_id = Session::get('merchant_id');
            $statement->created_at = date('d-m-Y H:i:s');
            $statement->created_by = Session::get('id');
            $statement->save();
         }
         $this->saved++;
         return new Statement([
            'voucher_id' => $voucher_id,
            'voucher_no' => $voucher_no,
            'head_id' => $head_id,
            'debit'    => $debit_amount,
            'credit' => $credit_amount,
            'bank_id'=>$bank,
            'fyear'=>$fyear,
            'remark' => $row[$description_col],
            'st_date' => date('Y-m-d',strtotime($row[$date_col])),
            'status' => $status,
            'cat_source' => $status == 1 ? 'rule' : null,
            'cat_rule_id' => $status == 1 ? ($applied_rule ?: null) : null,
            'merchant_id' => Session::get('merchant_id'),
            'created_at' => date('d-m-Y H:i:s'),
            'created_by' => Session::get('id'),
         ]);
      }
      
      
   }
}