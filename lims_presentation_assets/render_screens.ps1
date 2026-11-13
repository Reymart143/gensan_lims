Add-Type -AssemblyName System.Drawing
$dir = $PSScriptRoot
$font = [System.Drawing.FontFamily]::new('Arial')
$white = [System.Drawing.Brushes]::White
$navy = [System.Drawing.Color]::FromArgb(36,57,76)
$muted = [System.Drawing.Color]::FromArgb(102,122,140)
$blue = [System.Drawing.Color]::FromArgb(30,119,163)
$pale = [System.Drawing.Color]::FromArgb(234,243,249)
$line = [System.Drawing.Color]::FromArgb(229,235,240)
function DrawText($g,$text,$x,$y,$size=15,$color=$navy,$bold=$false) {
  $style=[System.Drawing.FontStyle]::Regular; if($bold){$style=[System.Drawing.FontStyle]::Bold}
  $f=[System.Drawing.Font]::new($font,$size,$style)
  $b=[System.Drawing.SolidBrush]::new($color)
  $g.DrawString([string]$text,$f,$b,[single]$x,[single]$y); $b.Dispose();$f.Dispose()
}
function Fill($g,$x,$y,$w,$h,$color,$radius=0) {
  $b=[System.Drawing.SolidBrush]::new($color)
  $g.FillRectangle($b,$x,$y,$w,$h)
  $b.Dispose()
}
function MakeShot($kind,$title,$subtitle,$data) {
  $bmp=[System.Drawing.Bitmap]::new(1440,900);$g=[System.Drawing.Graphics]::FromImage($bmp);$g.SmoothingMode=[System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
  $bg=[System.Drawing.Color]::FromArgb(244,247,251);$g.Clear($bg)
  Fill $g 0 0 220 900 ([System.Drawing.Color]::White); Fill $g 219 0 1 900 $line
  Fill $g 25 22 42 42 $pale 8;DrawText $g 'BFAR' 33 34 13 $blue $true;DrawText $g 'LABORATORY' 78 26 14 $blue $true;DrawText $g 'INFORMATION SYSTEM' 78 45 12 $blue $true
  $items=@('Overview','Customers','Laboratory Records','Equipment','Documents','Reports');$active=switch($kind){'clients'{'Customers'}'records'{'Laboratory Records'}'analysis'{'Laboratory Records'}'release'{'Reports'}'quality'{'Equipment'}default{'Overview'}}
  for($i=0;$i -lt $items.Count;$i++){ $y=105+$i*53; if($items[$i] -eq $active){Fill $g 14 $y 190 40 $pale 7};DrawText $g @('◉','♙','▤','⚙','▧','▥')[$i] 28 ($y+10) 16 $muted $true;DrawText $g $items[$i] 60 ($y+11) 14 $(if($items[$i] -eq $active){$blue}else{$muted}) ($items[$i] -eq $active) }
  DrawText $g 'BFAR • Laboratory Information System' 255 25 12 $muted;DrawText $g '● Staff workspace' 1260 25 12 $muted
  DrawText $g $title 255 70 27 $navy $true;DrawText $g $subtitle 255 111 14 $muted
  if($kind -eq 'dashboard') {
   $labels=@('Lab tests','Equipment','Job route forms','Releasing');$nums=@('128','34','42','19')
   for($i=0;$i -lt 4;$i++){ $x=255+$i*284;Fill $g $x 162 268 98 ([System.Drawing.Color]::White) 9;DrawText $g $labels[$i] ($x+18) 181 13 $muted;DrawText $g $nums[$i] ($x+18) 210 26 $navy $true }
   Fill $g 255 282 1140 346 ([System.Drawing.Color]::White) 10;DrawText $g 'Workflow activity' 273 300 15 $navy $true
   $s=@('01 · INTAKE','02 · REVIEW','03 · ANALYZE','04 · RELEASE');$d=@('Register client & sample','RLA & payment','Route to lab unit','Results & report')
   for($i=0;$i -lt 4;$i++){ $x=274+$i*277;Fill $g $x 346 264 65 $pale 8;DrawText $g $s[$i] ($x+14) 357 13 $blue $true;DrawText $g $d[$i] ($x+14) 382 13 $navy }
   DrawText $g 'Request and release activity' 274 439 14 $muted
   $pen=[System.Drawing.Pen]::new($blue,4);$pts=[System.Drawing.PointF[]]@([System.Drawing.PointF]::new(274,568),[System.Drawing.PointF]::new(420,540),[System.Drawing.PointF]::new(570,551),[System.Drawing.PointF]::new(725,495),[System.Drawing.PointF]::new(875,518),[System.Drawing.PointF]::new(1030,474),[System.Drawing.PointF]::new(1190,484),[System.Drawing.PointF]::new(1380,430));$g.DrawLines($pen,$pts);$pen.Dispose()
  } else {
   Fill $g 255 160 1145 65 ([System.Drawing.Color]::White) 8;DrawText $g $data[0] 275 182 13 $muted;DrawText $g 'Search     Clear' 1210 182 13 $blue $true
   Fill $g 255 246 1145 44 ([System.Drawing.Color]::FromArgb(248,250,252))
   $headers=$data[1];$width=1145.0/$headers.Count
   for($j=0;$j -lt $headers.Count;$j++){DrawText $g $headers[$j] (270+$j*$width) 260 11 $muted $true}
   for($i=0;$i -lt $data[2].Count;$i++){
    $y=290+$i*68;Fill $g 255 $y 1145 66 ([System.Drawing.Color]::White);Fill $g 255 ($y+65) 1145 1 $line
    for($j=0;$j -lt $data[2][$i].Count;$j++){DrawText $g $data[2][$i][$j] (270+$j*$width) ($y+21) 13 $navy}
   }
   if($kind -eq 'analysis'){
    Fill $g 255 545 1145 170 ([System.Drawing.Color]::White) 8;DrawText $g 'Analysis pathways supported' 276 561 15 $navy $true
    DrawText $g 'Chemistry  ·  Water quality · Moisture · Histamine' 279 601 13 $blue
    DrawText $g 'Microbiology  ·  Potability · Bacteriology · Fishery' 279 635 13 $blue
    DrawText $g 'Fisheries  ·  Parasitology / gross · PCR' 279 669 13 $blue
   }
  }
  DrawText $g 'SCREEN PREVIEW • Application labels and workflow reconstructed from project views; sample data is fictional' 255 823 11 $muted
  $bmp.Save((Join-Path $dir ($kind+'.png')),[System.Drawing.Imaging.ImageFormat]::Png);$g.Dispose();$bmp.Dispose()
}
MakeShot 'dashboard' 'Dashboard' 'Laboratory operations at a glance' @()
MakeShot 'clients' 'Clients List' 'Search client records and open a new sample request' @('Search clients by company or contact',@('COMPANY NAME','CONTACT','STATUS','JOIN DATE','ACTION'),@(@('South Coast Foods Co.','0917 ••• 0241','Active','11 Nov 2026','View  Edit'),@('Lakeview Water Services','0928 ••• 3850','Active','11 Nov 2026','View  Edit'),@('Harbor Fisheries Group','0998 ••• 1172','Active','11 Nov 2026','View  Edit'),@('Green Valley Producers','0918 ••• 6940','Active','11 Nov 2026','View  Edit')))
MakeShot 'records' 'Laboratory Records' 'Linked request, payment, routing and analysis stages' @('Search by laboratory code, customer or status',@('LAB CODE','CLIENT','TEST / UNIT','STAGE','STATUS'),@(@('LAB-2026-0412','South Coast Foods','Histamine · Chemistry','Worksheet','In progress'),@('LAB-2026-0413','Lakeview Water','Potability · Microbiology','Payment','For review'),@('LAB-2026-0414','Harbor Fisheries','PCR · Fisheries','Routing','Assigned'),@('LAB-2026-0415','Green Valley','Water quality · Chemistry','Released','Complete')))
MakeShot 'analysis' 'Analyst Worksheet' 'Request LAB-2026-0412 · Histamine analysis' @('Client: South Coast Foods  ·  Sample: Frozen tuna loin',@('PARAMETER','RESULT','UNIT','REFERENCE','METHOD'),@(@('Histamine','18.4','mg/kg','≤ 50 mg/kg','Validated'),@('Status','Draft','Worksheet','Save result','Review')))
MakeShot 'release' 'Releasing & Reports' 'Complete review and provide the documented result' @('Search release queue by laboratory code or client',@('LAB CODE','CLIENT','TEST','RESULT','OUTPUT'),@(@('LAB-2026-0408','Green Valley','Moisture','Reviewed','Ready to release'),@('LAB-2026-0409','South Coast Foods','Water quality','Reviewed','Report PDF'),@('LAB-2026-0410','Lakeview Water','Potability','Released','Completed'),@('LAB-2026-0411','Harbor Fisheries','PCR','In review','Pending')))
MakeShot 'quality' 'Quality & Resources' 'Supporting records for laboratory operations' @('Search equipment, logbooks and plans',@('MODULE','RECORD','STATUS','OWNER','ACTION'),@(@('Equipment','Usage and reports','Active','Lab staff','Open / Export'),@('Maintenance plan','Scheduled service','Upcoming','Equipment lead','View'),@('Environmental plan','Conditions','Current','Unit lead','View'),@('Quality documents','Manual and SOPs','Controlled','Staff','Open'),@('Logbooks','Sample / reagent','Active','Lab staff','Open')))
