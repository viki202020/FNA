@php
   $fnaMenu = [
      ['Dashboard', '/merchant', ['merchant']],
      ['head', 'Accounts'],
      ['Vouchers', '/merchant/vouchers', ['merchant/vouchers*', 'merchant/voucher/*']],
      ['Ledgers', '/merchant/ledgers', ['merchant/ledgers*', 'merchant/ledger-by-head/*']],
      ['Bank statement', '/merchant/statement', ['merchant/statement*']],
      ['head', 'Fin statements'],
      ['Trial balance', '/merchant/trial-balance', ['merchant/trial-balance*']],
      ['Balance sheet', '/merchant/balance-sheet', ['merchant/balance-sheet*']],
      ['Profit and loss', '/merchant/profit-loss', ['merchant/profit-loss*']],
      ['head', 'Upload'],
      ['Bank txns file', '/merchant/upload-csv', ['merchant/upload-csv*']],
      ['Journal txns file', '/merchant/upload-expencess-csv', ['merchant/upload-expencess-csv*']],
      ['Uncategorised entry', '/merchant/uncategorized-data', ['merchant/uncategorized-data*']],
      ['Receipts to match', '/merchant/receipts-to-match', ['merchant/receipts-to-match*']],
      ['Manage rules', '/merchant/manage-rules', ['merchant/manage-rules*']],
      ['Rule conflicts', '/merchant/rule-conflicts', ['merchant/rule-conflicts*']],
      ['Rule audit', '/merchant/rule-audit', ['merchant/rule-audit*']],
      ['head', 'Setup'],
      ['Company', '/merchant/entity', ['merchant/entity*']],
      ['Accounts', '/merchant/manage-account', ['merchant/manage-account*']],
      ['Account heads', '/merchant/account-head', ['merchant/account-head*']],
      ['Customers', '/merchant/customers', ['merchant/customers*']],
      ['Invoices', '/merchant/manage-invoice', ['merchant/manage-invoice*','merchant/invoices*']],
      ['Bills', '/merchant/manage-bill', ['merchant/manage-bill*','merchant/bills*']],
      ['Products', '/merchant/manage-product', ['merchant/manage-product*']],
      ['Close book', '/merchant/manage-book', ['merchant/manage-book*']],
   ];
@endphp
<nav id="sidebarMenu" class="sidebar fna-sidebar d-lg-block collapse">
   <div class="fna-brand">FNA<small>Mobilpay</small></div>
   @foreach($fnaMenu as $item)
      @if($item[0]=='head')
         <div class="fna-head">{{ $item[1] }}</div>
      @else
         <a href="{{ URL($item[1]) }}" class="fna-link @if(request()->is($item[2])) active @endif">{{ $item[0] }}</a>
      @endif
   @endforeach
   <div class="fna-foot">
      <a href="{{ URL('logout') }}" class="fna-link">Sign out</a>
   </div>
</nav>
