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
use Illuminate\Support\Str;
class UsersImport implements ToModel,WithStartRow,WithLimit
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
   public function  __construct($data){
      $this->data = $data;
      $this->voucherNumbers = VoucherNumber::sequence('SBV-',5);
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
      $rules = Rule::select('*')->where('status',1)->get();

      // The lines are saved whether or not any rule exists; rules (if any) only choose the account.
      {
         foreach ($rules as $k => $rule) {
            if($rule->rule_in==$bank){
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
               }
               if(!empty($head_id)){
                  break;
               }
            }
         }
         $entity = Entity::where('status',1)->where('merchant_id',Session::get('merchant_id'))->orderBy('id','desc')->first();
         $fyear = $entity->financial_year;
         // The bank's own entry is created for EVERY line, exactly as the bank shows it
         // (money in = debit on the bank, money out = credit), whether or not a rule matched.
         // Categorising later only chooses the account for the other, reversed entry.
         // one System Bank Voucher number per bank line, shared by both of its entries
         $voucher_no = $this->voucherNumbers->next();
         if(!empty($bank)){
            $statement = new Statement;
            $statement->voucher_no = $voucher_no;
            $statement->head_id = $bank;
            $statement->debit = $credit_amount;
            $statement->credit = $debit_amount;
            $statement->bank_id = $bank;
            $statement->fyear = $fyear;
            $statement->remark = $row[$description_col];
            $statement->st_date = date('Y-m-d',strtotime($row[$date_col]));
            $statement->status = 1;
            $statement->merchant_id = Session::get('id');
            $statement->created_at = date('d-m-Y H:i:s');
            $statement->created_by = Session::get('id');
            $statement->save();
         }
         return new Statement([
            'voucher_no' => $voucher_no,
            'head_id' => $head_id,
            'debit'    => $debit_amount,
            'credit' => $credit_amount,
            'bank_id'=>$bank,
            'fyear'=>$fyear,
            'remark' => $row[$description_col],
            'st_date' => date('Y-m-d',strtotime($row[$date_col])),
            'status' => $status,
            'merchant_id' => Session::get('id'),
            'created_at' => date('d-m-Y H:i:s'),
            'created_by' => Session::get('id'),
         ]);
      }
      
      
   }
}