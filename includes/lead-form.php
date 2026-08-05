<?php $mode=$mode??'consult'; $selected=$selected??''; ?>
<form class="c2c-lead-form" action="<?=e(url('handlers/submit-lead.php'))?>" method="post" enctype="multipart/form-data" data-ajax-form novalidate>
  <input type="hidden" name="mode" value="<?=e($mode)?>">
  <input type="hidden" name="page_url" value="<?=e((isset($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'localhost').($_SERVER['REQUEST_URI']??'/'))?>">
  <div class="hp-field" aria-hidden="true"><label>Company website<input name="company_website" tabindex="-1" autocomplete="off"></label></div>
  <div class="form-heading"><span><?=icon($mode==='estimate'?'camera':'calendar')?></span><div><p class="form-kicker"><?=$mode==='estimate'?'PROJECT REVIEW':'15-MINUTE INTRO CALL'?></p><h3><?=$mode==='estimate'?'Request an estimate review':'Book your consultation'?></h3></div></div>
  <div class="form-message" aria-live="polite"></div>
  <div class="field-grid"><label>Full name <span aria-hidden="true">*</span><input name="full_name" autocomplete="name" required></label><label>Email <span aria-hidden="true">*</span><input type="email" name="email" autocomplete="email" required></label></div>
  <div class="field-grid"><label>Phone <span aria-hidden="true">*</span><input type="tel" name="phone" autocomplete="tel" placeholder="(716) 555-0123" required></label><label>City or ZIP <span aria-hidden="true">*</span><input name="city_zip" autocomplete="postal-code" required></label></div>
  <label>Project type <span aria-hidden="true">*</span><select name="project_type" required><option value="">Choose a project</option><?php foreach(['Basement','Kitchen','Bathroom','Full-room / Multi-room'] as $v):?><option <?=stripos($v,$selected)!==false?'selected':''?>><?=e($v)?></option><?php endforeach;?></select></label>
  <div class="field-grid"><label>Target start<select name="start_window"><option>ASAP</option><option>1-2 months</option><option>3-6 months</option><option>Planning 6+ months</option></select></label><label>Budget range<select name="budget"><option>Not sure</option><option>Under $15,000</option><option>$15,000-$30,000</option><option>$30,000-$75,000</option><option>$75,000+</option></select></label></div>
  <label>Are you a decision maker?<select name="decision_maker"><option>Yes</option><option>No</option><option>Not sure</option></select></label>
  <?php if($mode==='estimate'):?>
    <label>Project address<input name="address" autocomplete="street-address"></label>
    <label>Project photos <span class="optional">JPG, PNG or PDF</span><input type="file" name="photos[]" accept=".jpg,.jpeg,.png,.pdf" multiple data-file-input><small>Upload up to <?=MAX_UPLOAD_FILES?> files, <?=MAX_UPLOAD_MB?> MB each. 3-8 photos are ideal: wide angles plus problem areas.</small><span class="file-summary" data-file-summary></span></label>
    <label>Project notes<textarea name="notes" rows="4" placeholder="Tell us what you want to change, known concerns, and desired outcome."></textarea></label>
  <?php else:?>
    <label>What would make this consultation useful?<textarea name="notes" rows="3" placeholder="Share the room, your priorities, and any questions."></textarea></label>
  <?php endif;?>
  <label>How did you first hear about us?<select name="source"><option>Google</option><option>Referral</option><option>Facebook</option><option>Drove by</option><option>Other</option></select></label>
  <button class="btn btn-primary btn-block" type="submit"><?=$mode==='estimate'?'Get My Estimate Review':'Book My Consult'?></button>
  <p class="form-microcopy">Your information is sent securely to our team, entered into ClickUp, and used only to coordinate your project.</p>
</form>
