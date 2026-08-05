<?php
/** @var array $lead */
/** @var string $clickUpUrl */
$rows = [
    'Lead ID' => $lead['lead_id'],
    'Lead type' => ucfirst($lead['mode']),
    'Routing' => $lead['route_tag'],
    'Name' => $lead['full_name'],
    'Email' => $lead['email'],
    'Phone' => $lead['phone'],
    'Project' => $lead['project_type'],
    'Address' => $lead['address'],
    'City / ZIP' => $lead['city_zip'],
    'Target start' => $lead['start_window'],
    'Budget' => $lead['budget'],
    'Decision maker' => $lead['decision_maker'],
    'Source' => $lead['source'],
    'Notes' => $lead['notes'],
    'Submitted' => $lead['created_at'],
];
?>
<!doctype html><html><body style="margin:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#1a1a1a"><div style="max-width:680px;margin:32px auto;background:#fff;border:1px solid #e3e7eb;border-radius:14px;overflow:hidden"><div style="background:#12294a;padding:28px 32px;color:#fff"><div style="color:#d9a520;font-size:12px;letter-spacing:2px;text-transform:uppercase">New website lead</div><h1 style="margin:8px 0 0;font-size:25px"><?=htmlspecialchars($lead['full_name'])?> - <?=htmlspecialchars($lead['project_type'])?></h1></div><div style="padding:30px 32px"><table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse"><?php foreach($rows as $label=>$value):?><tr><td style="width:155px;border-bottom:1px solid #edf0f2;color:#5a636b;font-size:14px;vertical-align:top"><strong><?=htmlspecialchars($label)?></strong></td><td style="border-bottom:1px solid #edf0f2;font-size:14px;vertical-align:top"><?=nl2br(htmlspecialchars($value !== '' ? $value : 'Not provided'))?></td></tr><?php endforeach;?></table><?php if($clickUpUrl !== ''):?><p style="margin:28px 0 0"><a href="<?=htmlspecialchars($clickUpUrl)?>" style="display:inline-block;background:#d9a520;color:#12294a;text-decoration:none;padding:13px 20px;border-radius:8px">Open lead in ClickUp</a></p><?php endif;?><p style="margin:24px 0 0;color:#5a636b;font-size:13px">Uploaded files are attached to the ClickUp task. Small files may also be attached to this email.</p></div></div></body></html>
