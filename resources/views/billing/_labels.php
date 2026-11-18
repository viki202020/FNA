<?php
   $S = $kind === 'sales';
   $L = $S
      ? ['title'=>'Invoices','one'=>'invoice','New'=>'New invoice','party'=>'Customer','list'=>'merchant/manage-invoice','base'=>'merchant/invoices','new'=>'merchant/invoices/new','series'=>'SIV','foot'=>'Sales invoices are numbered SIV and post to the books when you post them']
      : ['title'=>'Bills','one'=>'bill','New'=>'New bill','party'=>'Supplier','list'=>'merchant/manage-bill','base'=>'merchant/bills','new'=>'merchant/bills/new','series'=>'PBV','foot'=>'Purchase bills are numbered PBV and post to the books when you post them'];
   $chipClass = ['draft'=>'draft','open'=>'waiting','part'=>'waiting','paid'=>'paid','void'=>'void'];
   $chipText = ['draft'=>'Draft','open'=>'Unpaid','part'=>'Part paid','paid'=>'Paid','void'=>'Void'];
