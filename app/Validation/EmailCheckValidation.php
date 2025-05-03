<?php

namespace App\Validation;

class EmailCheckValidation
{

  public function email_check($value): bool
  {
    if(preg_match("/^(([a-z\d+_\-][a-z\d+\'\._\-]*[a-z\d+_\-])|([a-z\d+_\-]{1,2}))@((([a-z\d][a-z\d\-]{0,62}[a-z\d])|([a-z\d]))\.)+[a-z]{2,}$/i", $value)) {
       return TRUE;
     } else {
       return FALSE;
     }
  }

}
