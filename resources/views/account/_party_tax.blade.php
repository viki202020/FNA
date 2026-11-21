{{-- Tax details of a customer or supplier. Missing details are flagged when an invoice or bill is booked. --}}
<div class="row" style="margin-top:6px">
   <div class="col-12"><div class="fna-section-title" style="margin:6px 0">Tax details</div></div>
   <div class="col-md-3 mb-3"><label>PAN</label><input class="form-control" type="text" name="pan" maxlength="10" placeholder="e.g. ABCDE1234F" style="text-transform:uppercase"></div>
   <div class="col-md-3 mb-3"><label>TDS APPLICABLE?</label>
      <select class="form-select" name="tds_applicable"><option value="">Not decided</option><option value="Yes">Yes</option><option value="No">No</option></select></div>
   <div class="col-md-3 mb-3"><label>TDS SECTION</label>
      <select class="form-select" name="tds_section"><option value="">Select</option>
         @foreach(['194C'=>'194C Contractors','194J'=>'194J Professional / technical fees','194I'=>'194I Rent','194H'=>'194H Commission','194Q'=>'194Q Purchase of goods','194A'=>'194A Interest','194IA'=>'194IA Immovable property','195'=>'195 Non-resident','Other'=>'Other'] as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
   <div class="col-md-3 mb-3"><label>TDS RATE %</label><input class="form-control" type="text" name="tds_rate" placeholder="e.g. 10"></div>
</div>
