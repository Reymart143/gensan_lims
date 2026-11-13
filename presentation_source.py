from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
from html import escape
import subprocess, shutil, uuid, os, textwrap

ROOT=Path(__file__).parent
OUT=ROOT/'lims_presentation_assets'
OUT.mkdir(exist_ok=True)
CHROME=Path(r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe')
NAV=['Overview','Customers','Laboratory Records','Equipment','Documents','Reports']

def html_screen(title, subtitle, body, active, filename):
    nav=''.join(f'<div class="nav {"active" if x==active else ""}"><b>{["◉","♙","▤","⚙","▧","▥"][i]}</b>{x}</div>' for i,x in enumerate(NAV))
    html=f'''<!doctype html><html><head><meta charset="utf-8"><style>
    *{{box-sizing:border-box}}body{{margin:0;background:#f4f7fb;color:#243247;font:14px Arial,sans-serif}}.side{{position:absolute;inset:0 auto 0 0;width:220px;background:#fff;border-right:1px solid #e7ebf0;padding:22px 14px}}.brand{{display:flex;gap:12px;align-items:center;padding:6px 8px 28px;color:#174d78;font-weight:bold;font-size:15px}}.brand img{{width:43px;height:43px;object-fit:contain}}.nav{{padding:12px 10px;margin:5px 0;border-radius:8px;color:#637286;font-size:13px}}.nav b{{display:inline-block;width:29px;color:#7890a5;font-size:17px}}.nav.active{{background:#e8f2fa;color:#176a9a;font-weight:bold}}.main{{margin-left:220px;padding:27px 34px}}.top{{display:flex;justify-content:space-between;color:#718198;font-size:12px;margin-bottom:25px}}h1{{font-size:25px;margin:0 0 7px;color:#20394e}}.sub{{color:#718198;margin-bottom:24px}}.grid{{display:flex;gap:15px;margin:18px 0 22px}}.card{{background:white;border-radius:11px;border:1px solid #e6ebf1;padding:18px;box-shadow:0 2px 8px #152c3d08;flex:1}}.stat{{color:#718198;font-size:12px}}.value{{font-size:27px;font-weight:bold;color:#24425a;margin-top:11px}}.table{{width:100%;border-collapse:collapse;background:white;border-radius:10px;overflow:hidden;font-size:12px}}th{{background:#f7f9fb;color:#62768a;text-align:left;font-size:11px}}td,th{{padding:13px 12px;border-bottom:1px solid #edf0f4}}.badge{{background:#e5f5ee;color:#26845c;padding:5px 9px;border-radius:20px;font-size:10px}}.blue{{background:#1d78a9;color:#fff;padding:10px 14px;border-radius:7px;float:right;font-weight:bold;font-size:12px}}.section{{font-weight:bold;margin:7px 0 12px;color:#314a60}}.note{{color:#738598;font-size:11px}}.steps{{display:flex;gap:12px;margin-top:18px}}.step{{padding:14px 16px;background:#f2f7fb;border-radius:8px;flex:1;color:#365168;font-size:12px}}.step em{{display:block;font-style:normal;color:#2381a9;font-weight:bold;margin-bottom:8px}}
    </style></head><body><aside class="side"><div class="brand"><img src="file:///{(ROOT/'public/assets/images/bfarlogo.png').as_posix()}"><span>LABORATORY<br>INFORMATION SYSTEM</span></div>{nav}</aside><main class="main"><div class="top"><span>BFAR • Laboratory Information System</span><span>● Staff workspace</span></div>{body}<div class="note" style="margin-top:14px">SCREEN PREVIEW • Layout reconstructed from application views; sample data is fictional</div></main></body></html>'''
    f=OUT/(filename+'.html'); f.write_text(html,encoding='utf8')
    snapshot={'screen_dashboard':'dashboard','screen_clients':'clients','screen_records':'records','screen_analysis':'analysis','screen_release':'release','screen_quality':'quality'}.get(filename)
    if snapshot and (OUT/(snapshot+'.png')).exists(): return OUT/(snapshot+'.png')
    if CHROME.exists():
        png=OUT/(filename+'.png'); profile=OUT/('chrome_'+filename)
        subprocess.run([str(CHROME),'--headless','--disable-gpu','--disable-extensions','--disable-background-networking','--hide-scrollbars',f'--user-data-dir={profile}', '--window-size=1440,900',f'--screenshot={png}', f.as_uri()],capture_output=True,timeout=30)
        if png.exists(): return png
    return None

screens=[]
screens.append(html_screen('Overview','Operational snapshot', '''<h1>Dashboard</h1><div class="sub">Laboratory operations at a glance</div><div class="grid">'''+''.join(f'<div class="card"><div class="stat">{k}</div><div class="value">{v}</div></div>' for k,v in [('Lab tests','128'),('Equipment','34'),('Job route forms','42'),('Releasing','19')])+'''</div><div class="section">Workflow activity</div><div class="card"><div class="steps"><div class="step"><em>01 · INTAKE</em>Register client & sample</div><div class="step"><em>02 · REVIEW</em>RLA & payment</div><div class="step"><em>03 · ANALYZE</em>Route to lab unit</div><div class="step"><em>04 · RELEASE</em>Results & report</div></div><div style="height:190px;margin-top:22px;background:linear-gradient(180deg,#f7fbfd,#fff);border-bottom:1px solid #dee7ed;position:relative"><svg width="100%" height="180"><polyline points="0,145 130,120 260,132 390,83 520,104 650,56 780,74 910,30 1040,47" fill="none" stroke="#3294bf" stroke-width="4"/><polyline points="0,160 130,151 260,136 390,142 520,112 650,123 780,97 910,86 1040,68" fill="none" stroke="#83b8a2" stroke-width="3"/></svg></div></div>''','Overview','screen_dashboard'))
screens.append(html_screen('Client registry','Register and maintain customer records', '''<h1>Clients List</h1><div class="sub">Search client records and open a new sample request</div><div class="blue">＋ Add Client</div><div class="card" style="margin-bottom:18px">⌕ &nbsp; Search clients by company or contact <span style="float:right;color:#1f78a7">Search　 Clear</span></div><table class="table"><thead><tr><th>COMPANY NAME</th><th>CONTACT</th><th>STATUS</th><th>JOIN DATE</th><th>ACTION</th></tr></thead><tbody>'''+''.join(f'<tr><td>{c}</td><td>{phone}</td><td><span class="badge">Active</span></td><td>11 Nov 2026</td><td>View　 Edit</td></tr>' for c,phone in [('South Coast Foods Co.','0917 ••• 0241'),('Lakeview Water Services','0928 ••• 3850'),('Harbor Fisheries Group','0998 ••• 1172'),('Green Valley Producers','0918 ••• 6940')])+'''</tbody></table>''','Customers','screen_clients'))
screens.append(html_screen('Sample-to-report workflow','Request review, fee/payment, routing, analysis, release', '''<h1>Laboratory Records</h1><div class="sub">A request progresses through linked forms and status updates</div><div class="grid">'''+''.join(f'<div class="card"><div class="stat">{n}</div><div class="value" style="font-size:17px">{t}</div><p class="note">{d}</p></div>' for n,t,d in [('01','RLA request','Sample details · requested test'),('02','Order of payment','Charges · payment status'),('03','Job routing','Assign unit · analyst'),('04','Worksheet','Enter analysis results')])+'''</div><div class="section">Request queue</div><table class="table"><thead><tr><th>LAB CODE</th><th>CLIENT</th><th>TEST / UNIT</th><th>STAGE</th><th>STATUS</th></tr></thead><tbody><tr><td>LAB-2026-0412</td><td>South Coast Foods</td><td>Histamine · Chemistry</td><td>Worksheet</td><td><span class="badge">In progress</span></td></tr><tr><td>LAB-2026-0413</td><td>Lakeview Water</td><td>Potability · Microbiology</td><td>Payment</td><td>For review</td></tr><tr><td>LAB-2026-0414</td><td>Harbor Fisheries</td><td>PCR · Fisheries</td><td>Routing</td><td>Assigned</td></tr></tbody></table>''','Laboratory Records','screen_records'))
screens.append(html_screen('Analysis & result','Discipline-specific worksheets', '''<h1>Analyst Worksheet</h1><div class="sub">Request LAB-2026-0412 &nbsp; · &nbsp; Histamine analysis</div><div class="grid"><div class="card"><div class="section">Request details</div><p>Client　 South Coast Foods Co.</p><p>Sample　 Frozen tuna loin</p><p>Laboratory unit　 Chemistry</p><span class="badge">Routed to analyst</span></div><div class="card" style="flex:1.35"><div class="section">Result entry</div><table class="table"><tr><th>PARAMETER</th><th>RESULT</th><th>UNIT</th><th>REFERENCE</th></tr><tr><td>Histamine</td><td>18.4</td><td>mg/kg</td><td>≤ 50 mg/kg</td></tr><tr><td>Method</td><td colspan="3">Validated method / worksheet fields</td></tr></table><div style="margin-top:16px;color:#246f93;font-weight:bold">Save Worksheet　　Mark Ready for Review</div></div></div><div class="card"><div class="section">Analysis pathways supported</div><div class="steps"><div class="step">Chemistry<br><span class="note">Water quality · Moisture · Histamine</span></div><div class="step">Microbiology<br><span class="note">Water · Bacteriology · Fishery</span></div><div class="step">Fisheries<br><span class="note">Parasitology · Gross · PCR</span></div></div></div>''','Laboratory Records','screen_analysis'))
screens.append(html_screen('Release & reporting','Controlled outputs from recorded work', '''<h1>Releasing & Reports</h1><div class="sub">Complete review and provide the documented result</div><div class="steps"><div class="step"><em>01 · REVIEW</em>Worksheet status reviewed</div><div class="step"><em>02 · RELEASE</em>Release log & signature</div><div class="step"><em>03 · REPORT</em>Generate report of test</div></div><div class="section" style="margin-top:25px">Release queue</div><table class="table"><thead><tr><th>LAB CODE</th><th>CLIENT</th><th>TEST</th><th>RESULT</th><th>OUTPUT</th></tr></thead><tbody><tr><td>LAB-2026-0408</td><td>Green Valley Producers</td><td>Moisture</td><td>Reviewed</td><td><span class="badge">Ready to release</span></td></tr><tr><td>LAB-2026-0409</td><td>South Coast Foods</td><td>Water quality</td><td>Reviewed</td><td>Report PDF</td></tr></tbody></table><div class="grid"><div class="card"><div class="section">Client notices</div><span class="note">Optional SMS/text-blast module is present in the application routes.</span></div><div class="card"><div class="section">Operational reports</div><span class="note">Payments · Customers · Laboratory tests</span></div></div>''','Reports','screen_release'))
screens.append(html_screen('Quality & resource controls','Equipment, environment, quality documentation', '''<h1>Quality & Resources</h1><div class="sub">Supporting records that help sustain laboratory operations</div><div class="grid">'''+''.join(f'<div class="card"><div style="font-size:27px;color:#23799f">{icon}</div><div class="value" style="font-size:17px">{title}</div><p class="note">{desc}</p></div>' for icon,title,desc in [('⚙','Equipment','Inventory, usage logs, reports, export'),('◷','Maintenance plans','Scheduled equipment care'),('⌁','Environmental records','Monitor and record conditions'),('▤','Controlled documents','Quality manual, SOPs, work instructions'),('▣','Logbooks','Receiving, disposal, reagent/media'),('✓','Supporting forms','Sterility checks, certificates, surveys')])+'''</div><div class="card"><div class="section">Resource traceability</div><div class="steps"><div class="step">Record<br><span class="note">Log use or observation</span></div><div class="step">Maintain<br><span class="note">Plan & update the asset</span></div><div class="step">Review<br><span class="note">Search, report or export</span></div></div></div>''','Equipment','screen_quality'))

# Office Open XML slide authoring: native text boxes, shapes and flow diagrams stay editable.
EMU=914400; W=13.333; H=7.5; SW=int(W*EMU); SH=int(H*EMU)
slides=[]; images={}
NAVY='10283F'; BLUE='176B93'; CYAN='62B9CE'; INK='233A4A'; MUTED='657B8B'; PALE='EDF5F8'; WHITE='FFFFFF'; GOLD='E8B75B'; MINT='72B39C'

def xval(v): return str(v).replace('&','&amp;').replace('<','&lt;').replace('>','&gt;')

class Slide:
  def __init__(self,title='',kicker='SYSTEM OVERVIEW',dark=False):
    self.items=[]; self.n=0; self.dark=dark
    bg=NAVY if dark else 'F4F7F9'; self.rect(0,0,W,H,bg)
    if not dark:
      self.rect(0,0,.12,H,BLUE); self.text(.55,.35,12,.25,kicker,10,BLUE,True); self.text(.55,.77,12,.5,title,27,INK,True)
      self.rect(.55,7.18,12.2,.015,'DCE5EA'); self.text(.55,7.23,11,.18,'LABORATORY INFORMATION SYSTEM  ·  SYSTEM WALKTHROUGH',8,MUTED); self.text(12.18,7.23,.55,.18,f'{len(slides)+1:02}',8,BLUE,True,'r')
  def _shape(self,x,y,w,h,geom,fill,stroke='none',rad=False):
    self.n+=1; nv=self.n; ln=' <a:ln><a:noFill/></a:ln>' if stroke=='none' else f'<a:ln w="13000"><a:solidFill><a:srgbClr val="{stroke}"/></a:solidFill></a:ln>'
    self.items.append(f'''<p:sp><p:nvSpPr><p:cNvPr id="{nv}" name="Shape {nv}"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="{int(x*EMU)}" y="{int(y*EMU)}"/><a:ext cx="{int(w*EMU)}" cy="{int(h*EMU)}"/></a:xfrm><a:prstGeom prst="{geom}"><a:avLst/></a:prstGeom><a:solidFill><a:srgbClr val="{fill}"/></a:solidFill>{ln}</p:spPr></p:sp>''')
  def rect(self,x,y,w,h,c): self._shape(x,y,w,h,'rect',c)
  def round(self,x,y,w,h,c): self._shape(x,y,w,h,'roundRect',c)
  def text(self,x,y,w,h,t,sz=14,c=INK,b=False,align='l'):
    self.n+=1; nv=self.n; paras=[]
    for line in str(t).split('\n'):
      paras.append(f'<a:p><a:pPr algn="{align}"/><a:r><a:rPr lang="en-US" sz="{sz*100}" b="{int(b)}"><a:solidFill><a:srgbClr val="{c}"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t xml:space="preserve">{xval(line)}</a:t></a:r><a:endParaRPr lang="en-US" sz="{sz*100}"/></a:p>')
    pxml=''.join(paras)
    self.items.append(f'<p:sp><p:nvSpPr><p:cNvPr id="{nv}" name="Text {nv}"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="{int(x*EMU)}" y="{int(y*EMU)}"/><a:ext cx="{int(w*EMU)}" cy="{int(h*EMU)}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr><p:txBody><a:bodyPr wrap="square" anchor="ctr" lIns="0" rIns="0" tIns="0" bIns="0"/><a:lstStyle/>{pxml}</p:txBody></p:sp>')
  def bullet(self,x,y,w,lines,sz=14,color=INK,gap=.46):
    for i,s in enumerate(lines): self.text(x,y+i*gap,.2,.28,'•',sz,BLUE,True); self.text(x+.25,y+i*gap,w-.25,.34,s,sz,color)
  def pic(self,p,x,y,w,h):
    if not p: return
    rid=f'rIdImg{len(images)+1}'; images[rid]=Path(p)
    self.n+=1; nv=self.n
    self.items.append(f'<p:pic><p:nvPicPr><p:cNvPr id="{nv}" name="System screen preview"/><p:cNvPicPr><a:picLocks noChangeAspect="1"/></p:cNvPicPr><p:nvPr/></p:nvPicPr><p:blipFill><a:blip r:embed="{rid}"/><a:stretch><a:fillRect/></a:stretch></p:blipFill><p:spPr><a:xfrm><a:off x="{int(x*EMU)}" y="{int(y*EMU)}"/><a:ext cx="{int(w*EMU)}" cy="{int(h*EMU)}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></p:spPr></p:pic>')

def add(s): slides.append(s)

# Cover
s=Slide(dark=True); s.rect(.5,.55,.14,.35,CYAN); s.text(.9,.55,5,.27,'BFAR  ·  SYSTEM WALKTHROUGH',11,'B6DDEA',True)
s.text(.75,1.55,7.1,1.55,'Laboratory\nInformation System',36,WHITE,True); s.text(.78,3.45,6.8,.55,'From sample intake to a documented result',19,'A7C7D4')
s.rect(.78,4.45,1.0,.05,CYAN); s.text(.78,4.8,6.4,.85,'A guided overview of the workflows, laboratory modules,\nand operational records represented in the application.',13,'D0DDE4')
for i,(a,b) in enumerate([('01','INTAKE'),('02','ROUTE'),('03','ANALYZE'),('04','RELEASE')]):
 x=.78+i*1.68; s.round(x,6.25,1.45,.62,'1A3B53'); s.text(x+.12,6.36,.36,.22,a,12,CYAN,True); s.text(x+.48,6.36,.9,.22,b,10,WHITE,True)
add(s)
# Purpose
s=Slide('A shared workspace for laboratory operations','01  ·  SYSTEM AT A GLANCE')
s.text(.7,1.52,5.25,1.0,'One application connects customer records, sample requests, analysis records and operational support.',21,INK,True)
s.bullet(.75,3.05,5.2,['Customer and sample request handling','Laboratory records, payments and routing','Chemistry, microbiology and fisheries worksheets','Release, reporting and supporting quality records'],14)
s.round(6.55,1.55,5.9,4.85,WHITE); s.text(6.9,1.9,5.2,.3,'CAPABILITIES VISIBLE IN THE APPLICATION',11,BLUE,True)
for i,(n,a,b) in enumerate([('01','Request handling','Clients · RLA · payment'),('02','Lab execution','Job routing · worksheets'),('03','Result delivery','Release · report · notice'),('04','Quality support','Equipment · plans · documents')]):
 y=2.45+i*.88; s.round(6.9,y, .54,.52,PALE); s.text(7.0,y+.11,.36,.18,n,10,BLUE,True,'c'); s.text(7.7,y,3.8,.26,a,15,INK,True); s.text(7.7,y+.34,4.2,.25,b,11,MUTED)
add(s)
# workflow overview
s=Slide('The request moves through linked control points','02  ·  END-TO-END PROCESS')
steps=[('1','Register','Client + sample\nrequested test'),('2','Review & charge','RLA request\nOrder of payment'),('3','Route the work','Job routing\nunit + analyst'),('4','Analyze','Discipline worksheet\nresult recorded'),('5','Release','Review + release\nreport to client')]
for i,(n,t,d) in enumerate(steps):
 x=.68+i*2.53; s.round(x,2.0,2.15,2.02,WHITE); s.round(x+.17,2.18,.45,.45,BLUE if i<4 else MINT); s.text(x+.28,2.28,.22,.18,n,11,WHITE,True,'c'); s.text(x+.18,2.85,1.85,.28,t,15,INK,True); s.text(x+.18,3.28,1.9,.55,d,12,MUTED)
 if i<4: s.text(x+2.18,2.88,.34,.3,'›',23,CYAN,True,'c')
s.round(.75,4.55,11.9,1.3,PALE); s.text(1.05,4.81,11.2,.63,'System evidence: separate routes and status-update actions exist for RLA, payment, job routing, analyst worksheets, releasing and reports. The diagram summarizes this intended operational sequence.',14,INK)
add(s)
# Dashboard screenshot
s=Slide('Dashboard: an operational snapshot','03  ·  STAFF WORKSPACE')
s.pic(screens[0],.68,1.48,8.0,5.05); s.text(9.0,1.65,3.45,.3,'AT A GLANCE',11,BLUE,True)
s.bullet(9.0,2.15,3.5,['Lab tests / RLA requests','Equipment and routing forms','Releasing volume','Request and release activity'],14)
s.round(9.0,4.52,3.5,1.18,PALE); s.text(9.24,4.76,3.0,.65,'Dashboard totals are drawn from stored application records; values shown here are fictional.',11,INK)
add(s)
# Intake
s=Slide('Intake begins with a customer record','04  ·  CUSTOMER & SAMPLE')
s.pic(screens[1],.68,1.48,8.0,5.05); s.text(9.0,1.67,3.5,.3,'CUSTOMER RECORDS',11,BLUE,True)
s.bullet(9,2.17,3.55,['Search and browse clients','Create and edit client records','Public kiosk and intake routes are also present','Capture sample and test details in request forms'],14)
s.round(9,4.7,3.5,1.1,PALE); s.text(9.23,4.91,3,.62,'Example company names and contact details on this screen are fictional.',11,INK)
add(s)
# Records
s=Slide('Request control: review, payment and routing','05  ·  CASE MANAGEMENT')
s.pic(screens[2],.68,1.48,8.0,5.05); s.text(9.0,1.65,3.5,.3,'LINKED RECORDS',11,BLUE,True)
s.bullet(9,2.15,3.5,['RLA captures the laboratory request','Order of payment records payment status','Job routing assigns work to a lab unit','Status actions expose progression between stages'],13)
s.round(9,4.78,3.5,1.02,PALE); s.text(9.23,4.98,3,.62,'Forms link back to a request through its laboratory record ID.',11,INK)
add(s)
# Lab branches
s=Slide('Laboratory work branches by discipline','06  ·  WORKSHEETS')
s.pic(screens[3],.68,1.48,8.0,5.05); s.text(9.0,1.65,3.5,.3,'ANALYSIS MODULES',11,BLUE,True)
s.bullet(9,2.1,3.6,['Chemistry: water quality, moisture, histamine','Microbiology: potability, bacteriology, fishery','Fisheries: parasitology / gross and PCR','Reagent/media and sterility support records'],12)
s.round(9,4.9,3.5,.95,PALE); s.text(9.23,5.06,3.0,.58,'Each worksheet family has its own form and save path.',11,INK)
add(s)
# Results
s=Slide('Review results, record release and report','07  ·  OUTPUTS')
s.pic(screens[4],.68,1.48,8.0,5.05); s.text(9.0,1.65,3.5,.3,'RESULT DELIVERY',11,BLUE,True)
s.bullet(9,2.15,3.5,['Worksheet completion has a status update route','Releasing log and signature capture are available','Report-of-test PDF route is present','Reporting views summarize payments, clients and tests'],13)
s.round(9,4.78,3.5,1.02,PALE); s.text(9.23,4.98,3.0,.62,'Actual approval rules and local operating policy should be confirmed with staff.',11,INK)
add(s)
# supporting
s=Slide('Supporting controls sustain day-to-day work','08  ·  QUALITY & RESOURCES')
s.pic(screens[5],.68,1.48,8.0,5.05); s.text(9.0,1.65,3.5,.3,'RELATED RECORDS',11,BLUE,True)
s.bullet(9,2.15,3.6,['Equipment inventory, usage log and reports','Equipment maintenance and environmental plans','Quality manual, SOPs, work instructions, log forms','Sample handling, reagent, sterility, certificates and survey'],12)
s.round(9,4.95,3.5,.9,PALE); s.text(9.23,5.11,3.0,.55,'These complement the analytical request lifecycle.',11,INK)
add(s)
# implementation map
s=Slide('How the application is organized','09  ·  SYSTEM MAP')
for i,(t,d) in enumerate([('People','Users · clients · profile'),('Records','RLA · payment · routing · worksheets'),('Outputs','Release · PDF forms · reports'),('Support','Equipment · quality docs · plans')]):
 x=.7+i*3.15; s.round(x,1.75,2.72,1.48,WHITE); s.text(x+.19,2.02,2.35,.3,t,17,BLUE,True); s.text(x+.19,2.51,2.36,.45,d,11,MUTED)
s.text(.76,3.72,5.5,.34,'IMPLEMENTATION EVIDENCE',11,BLUE,True)
s.bullet(.77,4.17,5.6,['Laravel application with routed controllers and server-rendered views','MySQL configured for the persistent application data','PDF output routes for key forms and reports'],13,gap=.48)
s.text(6.75,3.72,5.5,.34,'PRESENTATION SCOPE',11,BLUE,True)
s.bullet(6.75,4.17,5.7,['Workflow is reconstructed from routes, controllers and screen views','Live UI screenshots were unavailable: local MySQL/session connection failed','All dashboard counts and example client records are illustrative'],13,gap=.48)
add(s)
# close
s=Slide(dark=True); s.rect(.58,.54,.13,.32,CYAN); s.text(.9,.54,5,.24,'BFAR  ·  LABORATORY OPERATIONS',10,'B6DDEA',True)
s.text(.75,1.55,9.2,.72,'From request to result, with the record trail in view.',29,WHITE,True)
s.text(.78,2.6,8.5,.54,'Customer → request → payment → routing → analysis → release',16,'A7C7D4')
for i,(title,desc,col) in enumerate([('Receive','Record the client, sample and requested work.',BLUE),('Execute','Route the work and capture discipline-specific results.',CYAN),('Deliver','Review, release and provide a documented report.',MINT)]):
 x=.8+i*4.1; s.round(x,4.0,3.65,1.5,'1A3B53'); s.rect(x,4,.06,1.5,col); s.text(x+.24,4.23,3.05,.32,title,17,WHITE,True); s.text(x+.24,4.72,3.08,.56,desc,12,'D0DDE4')
s.text(.8,6.6,11,.25,'Overview created from the current project routes, controllers, models and Blade views  ·  11 Nov 2026',10,'A7C7D4')
add(s)

def slide_xml(s):
 return f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:cSld><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>{''.join(s.items)}</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sld>'''

def package():
 target=ROOT/'LIMS_System_Overview.pptx'
 with ZipFile(target,'w',ZIP_DEFLATED) as z:
  z.writestr('[Content_Types].xml','''<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/><Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/><Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/><Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>'''+''.join(f'<Override PartName="/ppt/slides/slide{i}.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>' for i in range(1,len(slides)+1))+'</Types>')
  z.writestr('_rels/.rels','''<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/></Relationships>''')
  z.writestr('ppt/presentation.xml',f'''<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId1"/></p:sldMasterIdLst><p:sldIdLst>{''.join(f'<p:sldId id="{255+i}" r:id="rId{i+2}"/>' for i in range(len(slides)))}</p:sldIdLst><p:sldSz cx="{SW}" cy="{SH}" type="screen16x9"/><p:notesSz cx="6858000" cy="9144000"/></p:presentation>''')
  z.writestr('ppt/_rels/presentation.xml.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>'+''.join(f'<Relationship Id="rId{i+2}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide{i+1}.xml"/>' for i in range(len(slides)))+'</Relationships>')
  z.writestr('ppt/slideMasters/slideMaster1.xml','''<p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:cSld><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld><p:clrMap accent1="176B93" accent2="62B9CE" accent3="72B39C" accent4="E8B75B" accent5="10283F" accent6="657B8B" bg1="FFFFFF" bg2="F4F7F9" folHlink="954F72" hlink="0563C1" lt1="FFFFFF" lt2="F4F7F9" dk1="000000" dk2="233A4A" tx1="000000" tx2="233A4A"/><p:sldLayoutIdLst><p:sldLayoutId id="1" r:id="rId1"/></p:sldLayoutIdLst><p:txStyles><p:titleStyle/><p:bodyStyle/><p:otherStyle/></p:txStyles></p:sldMaster>''')
  z.writestr('ppt/slideMasters/_rels/slideMaster1.xml.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/></Relationships>')
  z.writestr('ppt/slideLayouts/slideLayout1.xml','''<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="blank" preserve="1"><p:cSld name="Blank"><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sldLayout>''')
  z.writestr('ppt/slideLayouts/_rels/slideLayout1.xml.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/></Relationships>')
  z.writestr('ppt/theme/theme1.xml','''<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="BFAR LIMS"><a:themeElements><a:clrScheme name="LIMS"><a:dk1><a:srgbClr val="10283F"/></a:dk1><a:lt1><a:srgbClr val="FFFFFF"/></a:lt1><a:dk2><a:srgbClr val="233A4A"/></a:dk2><a:lt2><a:srgbClr val="F4F7F9"/></a:lt2><a:accent1><a:srgbClr val="176B93"/></a:accent1><a:accent2><a:srgbClr val="62B9CE"/></a:accent2><a:accent3><a:srgbClr val="72B39C"/></a:accent3><a:accent4><a:srgbClr val="E8B75B"/></a:accent4><a:accent5><a:srgbClr val="657B8B"/></a:accent5><a:accent6><a:srgbClr val="A7C7D4"/></a:accent6><a:hlink><a:srgbClr val="0563C1"/></a:hlink><a:folHlink><a:srgbClr val="954F72"/></a:folHlink></a:clrScheme><a:fontScheme name="Aptos"><a:majorFont><a:latin typeface="Aptos Display"/></a:majorFont><a:minorFont><a:latin typeface="Aptos"/></a:minorFont></a:fontScheme><a:fmtScheme name="Office"><a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:fillStyleLst><a:lnStyleLst><a:ln w="6350"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln></a:lnStyleLst><a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle></a:effectStyleLst><a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:bgFillStyleLst></a:fmtScheme></a:themeElements></a:theme>''')
  for i,s in enumerate(slides,1):
   z.writestr(f'ppt/slides/slide{i}.xml',slide_xml(s)); rels=[]
   for j,item in enumerate(s.items):
    # Image relationship ids appear only on image shape XML.
    for rid,p in images.items():
     if f'r:embed="{rid}"' in item:
      dest=f'ppt/media/screen_{rid}.png'; z.write(p,dest); rels.append(f'<Relationship Id="{rid}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/screen_{rid}.png"/>')
   rels.append('<Relationship Id="rIdLayout" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>')
   z.writestr(f'ppt/slides/_rels/slide{i}.xml.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'+''.join(rels)+'</Relationships>')
 return target

if __name__=='__main__':
 f=package(); print(f'{f} | slides={len(slides)} | screenshots={sum(bool(x) for x in screens)} | bytes={f.stat().st_size}')
