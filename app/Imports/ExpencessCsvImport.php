<?php
namespace App\Imports;
use App\Models\Statement;
use App\Models\Rule;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Session;
use Illuminate\Support\Str;
class ExpencessCsvImport implements ToModel,WithStartRow,WithLimit
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
   public function  __construct($data){
      $this->data = $data;
   }
   public function startRow(): int{
      return $this->data['header_row']+1;
   }
   public function limit(): int
   {
      return $this->data['last_row'];
   }
   public function model(array $row){
      array_unshift($row,"");
      unset($row[0]);

      $amount_column = $this->data['amount_column'];
      $date_col = $this->data['date_col'];
      $description_col = $this->data['description_col'];
      $amount_col = $this->data['amount_col'];
      $debit_amount_col = $this->data['debit_amount_col'];
      $credit_amount_col = $this->data['credit_amount_col'];
      $serial_col = $this->data['serial_col'];
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
      if(count($rules)>0){
         foreach ($rules as $k => $rule) {
            if($rule->col_type=="Amount"){
               $cond1 = $rule->rule_condition;
               if($rule->rule_condition=="Exact"){
                  $cond1 = "==";
               }
               if($rule->rule_for=="Debit"){
                  switch($cond1){
                     case ">=":
                        if($debit_amount>=$rule->content){
                           if($rule->rule_condition1!="Any"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        } 
                        break;
                     case "<=":
                        if($debit_amount<=$rule->content){
                           if($rule->rule_condition1!="Any"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }
                        break;
                     case "<":
                        if($debit_amount<$rule->content){
                           if($rule->rule_condition1!="Any"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        } 
                        break;
                     case ">":
                        if($debit_amount>$rule->content){
                           if($rule->rule_condition1!="Any"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }
                        break;
                     case "==":
                        if($debit_amount==$rule->content){
                           if($rule->rule_condition1!="Any"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        } 
                        break;
                  }           
               }else if($rule->rule_for=="Credit"){
                  switch($cond1){
                     case ">=":
                        if($credit_amount>=$rule->content){
                           if($rule->rule_condition1!="Any"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        } 
                        break;
                     case "<=":
                        if($credit_amount<=$rule->content){
                           if($rule->rule_condition1!="Any"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }
                        break;
                     case "<":
                        if($credit_amount<$rule->content){
                           if($rule->rule_condition1!="Any"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        } 
                        break;
                     case ">":
                        if($credit_amount>$rule->content){
                           if($rule->rule_condition1!="Any"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }
                        break;
                     case "==":
                        if($credit_amount==$rule->content){
                           if($rule->rule_condition1!="Any"){
                              if($row[$description_col]==$rule->content1){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        } 
                        break;
                  }
               }
            }else if($rule->col_type=="Description"){ 
               $cond1 = $rule->rule_condition;
               if($rule->rule_condition=="Exact"){
                  if($rule->rule_for=="Debit"){
                     if($row[$description_col]==$rule->content){
                        if($rule->rule_condition1!="Any"){
                           $cond2 = $rule->rule_condition1;
                           if($rule->rule_condition1 == "Exact"){
                              $cond2 = "==";
                           }
                           switch($cond2){
                              case ">=":
                                 if($debit_amount>=$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($row[$description_col]==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                              case "<=":
                                 if($debit_amount<=$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($row[$description_col]==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }
                                 break;
                              case "<":
                                 if($debit_amount<$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($row[$description_col]==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                              case ">":
                                 if($debit_amount>$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($row[$description_col]==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }
                                 break;
                              case "==":
                                 if($debit_amount==$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($row[$description_col]==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     }
                  }else if($rule->rule_for=="Credit"){
                    if($row[$description_col]==$rule->content){
                        if($rule->rule_condition1!="Any"){
                           $cond2 = $rule->rule_condition1;
                           if($rule->rule_condition1 == "Exact"){
                              $cond2 = "==";
                           }
                           switch($cond2){
                              case ">=":
                                 if($credit_amount>=$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($row[$description_col]==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                              case "<=":
                                 if($credit_amount<=$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($row[$description_col]==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }
                                 break;
                              case "<":
                                 if($credit_amount<$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($row[$description_col]==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                              case ">":
                                 if($credit_amount>$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($row[$description_col]==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }
                                 break;
                              case "==":
                                 if($credit_amount==$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($row[$description_col]==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     }
                  }
               }else{
                  if($rule->rule_for=="Debit"){
                     if($rule->rule_condition1!="Any"){
                        $cond2 = $rule->rule_condition1;
                        if($rule->rule_condition1 == "Exact"){
                           $cond2 = "==";
                        }
                        switch($cond2){
                           case ">=":
                              if($debit_amount>=$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($row[$description_col]==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                           case "<=":
                              if($debit_amount<=$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($row[$description_col]==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }
                              break;
                           case "<":
                              if($debit_amount<$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($row[$description_col]==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                           case ">":
                              if($debit_amount>$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($row[$description_col]==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }
                              break;
                           case "==":
                              if($debit_amount==$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($row[$description_col]==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                        }  
                     }else{
                        $check_status = 0;
                        if($rule->rule_condition=="Any"){
                           $content = strtoupper($rule->content);
                           $search = strtoupper($row[$description_col]);                        
                           if(Str::contains($search, $content)==true){
                              $check_status = 1;
                           }
                        }else if($rule->rule_condition=="Exact"){
                           if($row[$description_col]==$rule->content){
                              $check_status = 1;
                           }
                        }
                        
                        if($check_status==1){
                           if($rule->rule_condition1=="Any"){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }else{
                              $cond2 = $rule->rule_condition1;
                              if($rule->rule_condition1 == "Exact"){
                                 $cond2 = "==";
                              }
                              switch($cond2){
                                 case ">=":
                                    if($debit_amount>=$rule->content1){   
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    } 
                                    break;
                                 case "<=":
                                    if($debit_amount<=$rule->content1){ 
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                    break;
                                 case "<":
                                    if($debit_amount<$rule->content1){  
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    } 
                                    break;
                                 case ">":
                                    if($credit_amount>$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                    break;
                                 case "==":
                                    if($debit_amount==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    } 
                                    break;
                              }
                           }
                        }
                     }
                  }else if($rule->rule_for=="Credit"){
                     if($rule->rule_condition1!="Any"){
                        $cond2 = $rule->rule_condition1;
                        if($rule->rule_condition1 == "Exact"){
                           $cond2 = "==";
                        }
                        switch($cond2){
                           case ">=":
                              if($credit_amount>=$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($row[$description_col]==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                           case "<=":
                              if($credit_amount<=$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($row[$description_col]==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }
                              break;
                           case "<":
                              if($credit_amount<$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($row[$description_col]==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                           case ">":
                              if($credit_amount>$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($row[$description_col]==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }
                              break;
                           case "==":
                              if($credit_amount==$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($row[$description_col]==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                        }
                     }else{
                        $check_status = 0;
                        if($rule->rule_condition=="Any"){
                           $content = strtoupper($rule->content);
                           $search = strtoupper($row[$description_col]);                        
                           if(Str::contains($search, $content)==true){
                              $check_status = 1;
                           }
                        }else if($rule->rule_condition=="Exact"){
                           if($row[$description_col]==$rule->content){
                              $check_status = 1;
                           }
                        }
                        
                        if($check_status==1){
                           if($rule->rule_condition1=="Any"){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }else{
                              $cond2 = $rule->rule_condition1;
                              if($rule->rule_condition1 == "Exact"){
                                 $cond2 = "==";
                              }
                              switch($cond2){
                                 case ">=":
                                    if($credit_amount>=$rule->content1){   
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    } 
                                    break;
                                 case "<=":
                                    if($credit_amount<=$rule->content1){ 
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                    break;
                                 case "<":
                                    if($credit_amount<$rule->content1){  
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    } 
                                    break;
                                 case ">":
                                    if($credit_amount>$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                    break;
                                 case "==":
                                    if($credit_amount==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    } 
                                    break;
                              }
                           }
                        } 
                     }
                  }            
               }         
            }
            if(!empty($head_id)){
               break;
            }
         }
         if(!empty($row[$date_col])){
            return new Statement([
               'head_id'     => $head_id,
               'debit'    => $debit_amount,
               'credit' => $credit_amount,
               'remark' => $row[$description_col],
               'st_date' => date('Y-m-d',strtotime($row[$date_col])),
               'voucher_no'=>$row[$serial_col],
               'status' => $status,
               'merchant_id' => Session::get('id'),
               'created_at' => date('d-m-Y H:i:s'),
               'created_by' => Session::get('id'),
            ]);
         }
      }
   }
}