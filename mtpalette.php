<?php
declare(strict_types=1);
error_reporting(E_ALL);
set_error_handler(static function(int $severity,string $message): never {throw new ErrorException($message,0,$severity);});
$home=getenv('HOME');
if(!$home)throw new RuntimeException('HOME is unavailable');
$root=$home.'/domains/milagrestransparente.com/public_html';
$stage=$home.'/.milagres-deploy/mt-palette-20261106';
if(!is_dir($stage)&&!mkdir($stage,0700,true))throw new RuntimeException('Cannot create private staging directory');
$lock=fopen($stage.'/lock','c');
if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Another palette deployment is in progress');
if(is_file($stage.'/done.json')){echo "ALREADY_APPLIED\n";exit(0);}
function ghblob(string $str):string {return sha1('blob '.strlen($str)."\0".$str);}
function writeAtomic(string $filename,string $content):void {
 $temp=$filename.'.mtpal-tmp';
 if(file_put_contents($temp,$content,LOCK_EX)!==strlen($content))throw new RuntimeException('Write failed: '.$filename);
 if(!rename($temp,$filename))throw new RuntimeException('Rename failed: '.$filename);
}
$css=<<<'MT_NEW_CSS_END'
/* Self-hosted OFL font, limited to the privacy controls. No Google Fonts call. */
@font-face{font-family:Privacy Jakarta;src:url('/assets/privacy-plus-jakarta-latin.woff2') format('woff2');font-style:normal;font-weight:400 800;font-display:swap}
#cookie-banner.cookie-banner,#privacy-policy{font-family:Privacy Jakarta,system-ui,sans-serif;letter-spacing:normal;text-align:left;box-sizing:border-box;font-size:16px;font-weight:400;line-height:1.5}
#cookie-banner.cookie-banner *,#privacy-policy *{box-sizing:border-box}
#cookie-banner.cookie-banner[hidden],#cookie-banner [hidden]{display:none!important}
#cookie-banner.cookie-banner{display:block;position:fixed;z-index:80;left:max(16px,env(safe-area-inset-left));right:auto;bottom:var(--privacy-bottom,16px);width:min(512px,calc(100% - 32px));max-height:calc(100dvh - var(--privacy-bottom,16px) - 16px);padding:24px;overflow-y:auto;overscroll-behavior:contain;transform:none;gap:normal;border:1px solid #00c4f54d;border-radius:16px;background:#003153;color:#fff;box-shadow:0 25px 50px -12px #0004;backdrop-filter:none;animation:privacy-enter .2s ease-out}
#cookie-banner .privacy-heading{display:flex;flex-direction:row;align-items:flex-start;justify-content:space-between;gap:12px;margin:0}
#cookie-banner .privacy-heading-title{display:flex;align-items:center;gap:10px;min-width:0}
#cookie-banner .privacy-cookie-icon{width:36px;height:36px;display:grid;place-items:center;flex:none;border-radius:12px;background:#00c4f526;color:#00c4f5}
#cookie-banner .privacy-cookie-icon svg{width:20px;height:20px}
#cookie-banner h2{font:800 16px/24px Privacy Jakarta,system-ui,sans-serif;color:#fff;margin:0;letter-spacing:normal;text-transform:none}
#cookie-banner button,#privacy-policy button{font-family:inherit;cursor:pointer;text-transform:none;letter-spacing:normal;box-shadow:none;transition:background-color .15s,color .15s,border-color .15s}
#cookie-banner [data-privacy-toggle]{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid #00c4f540;border-radius:8px;background:#164865;color:#f4fcff;font-size:12px;line-height:16px;font-weight:600;flex:none;min-height:30px;position:relative}
#cookie-banner [data-privacy-toggle]::after{content:'';position:absolute;inset:-7px 0}
#cookie-banner [data-privacy-toggle] svg{width:14px;height:14px}
#cookie-banner [data-privacy-toggle]:hover{background:#205b79;color:#00c4f5}
#cookie-banner.cookie-banner p.privacy-banner-copy{margin:12px 0 0;color:#eaf7fb;font-size:14px;line-height:20px;max-width:none}
#cookie-banner .privacy-banner-copy a{font-weight:700;text-decoration:underline;text-underline-offset:1px;display:inline-block;color:#00c4f5}
#cookie-banner .privacy-banner-copy a:hover{color:#8be9ff}
#cookie-banner.cookie-banner #privacy-categories{margin-top:16px;padding-top:12px;border-top:1px solid #00c4f540}
#cookie-banner.cookie-banner .privacy-category{display:flex;align-items:center;justify-content:space-between;gap:8px;min-height:70px;margin:0;padding:10px;border:1px solid #00c4f536;background:#164865;border-radius:12px;color:#fff;font-size:12px;line-height:16px}
#cookie-banner .privacy-category+.privacy-category{margin-top:10px}
#cookie-banner .privacy-category label{margin:0}
#cookie-banner .privacy-category h3,#cookie-banner .privacy-category strong{display:block;margin:0;color:#fff;font:700 12px/16px Privacy Jakarta,system-ui,sans-serif;letter-spacing:normal}
#cookie-banner.cookie-banner .privacy-category p,#cookie-banner .privacy-category>span>span{display:block;margin:0;color:#d4d4d8;font-size:11px;line-height:16px}
#cookie-banner .privacy-category>span:first-child{padding-right:4px}
#cookie-banner label.privacy-category{cursor:pointer;min-height:70px}
#cookie-banner label.privacy-category:hover{background:#1c5573}
#cookie-banner .privacy-always-active{flex:none;padding:4px 10px;border-radius:999px;border:1px solid #65a51c66;background:#65a51c40;color:#6ee7b7;font-size:10px;line-height:15px;font-weight:800;white-space:nowrap}
#cookie-banner .privacy-category input{flex:none;width:16px;height:16px;margin:0;accent-color:#00c4f5;cursor:pointer}
#cookie-banner .privacy-actions{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:16px}
#cookie-banner #privacy-settings{display:flow-root;width:100%}
body.home-page:has(#cookie-banner input:focus):not(:has(dialog[open])) .whatsapp-float{visibility:visible;pointer-events:auto}
#cookie-banner.cookie-banner .privacy-choice{display:flex;justify-content:center;align-items:center;gap:6px;flex:1;min-width:130px;min-height:40px;margin:0;padding:10px 16px;border:0;border-radius:12px;background:#00c4f5;color:#003153;font-size:14px;font-weight:800;line-height:20px;white-space:normal;text-align:center}
#cookie-banner.cookie-banner .privacy-choice::after{content:'';position:absolute;inset:-2px 0}
#cookie-banner.cookie-banner .privacy-choice{position:relative}
#cookie-banner.cookie-banner [data-consent=essential]{min-width:140px;border:1px solid #00c4f540;background:#164865;color:#fff;font-weight:700}
#cookie-banner.cookie-banner [data-privacy-save]{flex:none;min-width:0;background:#df9137;color:#003153;font-weight:700}
#cookie-banner [data-privacy-save] svg{width:16px;height:16px;flex:none}
#cookie-banner [data-consent=all]:hover{background:#69def9}
#cookie-banner [data-consent=essential]:hover{background:#205b79}
#cookie-banner [data-privacy-save]:hover{background:#ecac5c}
#cookie-banner :focus-visible,#privacy-policy :focus-visible{outline:3px solid #00c4f5;outline-offset:3px}
.privacy-sr-only,.privacy-status{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip-path:inset(50%);white-space:nowrap}
/* Native dialog on ordinary pages; existing cinema dialog adapter after playback. */
html.privacy-policy-scroll-lock,html.privacy-policy-scroll-lock body{overflow:hidden!important}
#privacy-policy{width:min(768px,calc(100% - 48px));max-width:none;max-height:88dvh;padding:32px;margin:auto;border:1px solid #e2e8f0;border-radius:24px;background:#fff;color:#1e293b;box-shadow:0 25px 50px -12px #0004;overflow:auto;overscroll-behavior:contain}
#privacy-policy::backdrop{background:#020617cc;backdrop-filter:blur(12px)}
html.privacy-policy-open .local-dialog-backdrop{background:#020617cc;backdrop-filter:blur(12px)}
#privacy-policy .privacy-policy-close{position:absolute;top:20px;right:20px;display:grid;place-items:center;width:40px;height:40px;padding:0;border:0;border-radius:50%;background:#eaf4f9;color:#003153}
#privacy-policy .privacy-policy-close::after{content:'';position:absolute;inset:-2px}
#privacy-policy .privacy-policy-close svg{width:20px;height:20px}
#privacy-policy .privacy-policy-close:hover{background:#e2e8f0}
#privacy-policy .privacy-policy-heading{display:flex;align-items:center;gap:12px;padding-right:40px;margin-bottom:16px}
#privacy-policy .privacy-shield-icon{display:grid;place-items:center;flex:none;width:44px;height:44px;border-radius:16px;background:#00c4f526;color:#006c90}
#privacy-policy .privacy-policy-kicker{display:block;color:#006c90;font-size:11px;font-weight:800;line-height:16.5px;letter-spacing:.55px}
#privacy-policy h2{font:800 24px/32px Privacy Jakarta,system-ui,sans-serif;letter-spacing:normal;color:#003153;margin:0}
#privacy-policy #privacy-policy-meta{margin:0 0 20px;padding-bottom:16px;border-bottom:1px solid #e2e8f0;font-size:12px;line-height:16px;color:#475569}
#privacy-policy .privacy-policy-summary{padding:16px;border:1px solid #cce7f0;background:#edf8fc;border-radius:16px;margin-bottom:24px}
#privacy-policy h3{margin:0 0 6px;color:#003153;letter-spacing:normal;font:800 16px/24px Privacy Jakarta,system-ui,sans-serif}
#privacy-policy .privacy-policy-summary h3{font-size:14px;line-height:20px;margin-bottom:8px}
#privacy-policy :is(p,ul){margin:0;color:#24445a;font-size:14px;line-height:20px}
#privacy-policy ul{padding-left:16px;list-style:disc}
#privacy-policy li+li{margin-top:8px}
#privacy-policy .privacy-policy-summary li+li{margin-top:6px}
#privacy-policy .privacy-policy-sections>section+section{margin-top:20px}
#privacy-policy .privacy-policy-sections :is(p,ul){line-height:1.625}
#privacy-policy p+p{margin-top:8px}
#privacy-policy a{color:#006b8f;text-decoration:underline;text-underline-offset:2px;overflow-wrap:anywhere}
#privacy-policy code{font-size:.9em;overflow-wrap:anywhere}
#privacy-policy .privacy-policy-table{overflow-x:auto;border:1px solid #e2e8f0;border-radius:12px;margin:8px 0}
#privacy-policy table{border-collapse:collapse;width:100%;font-size:12px;line-height:16px;text-align:left}
#privacy-policy :is(th,td){padding:10px;vertical-align:top;border-bottom:1px solid #e2e8f0;overflow-wrap:anywhere}
#privacy-policy thead{background:#003153;color:#fff}
#privacy-policy tbody th{font-weight:700;color:#003153}
#privacy-policy tr:last-child :is(th,td){border-bottom:0}
#privacy-policy .privacy-policy-active{color:#3b630e;font-weight:700}
#privacy-policy .privacy-policy-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;border-top:1px solid #e2e8f0;margin-top:28px;padding-top:20px}
#privacy-policy .privacy-policy-actions>div{display:flex;flex-wrap:wrap;gap:8px}
#privacy-policy .privacy-policy-actions :is(button,a),#privacy-policy .privacy-media-stop{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 16px;min-height:44px;border:0;border-radius:12px;background:#f1f5f9;color:#1e293b;font-family:inherit;font-size:12px;line-height:16px;font-weight:700}
#privacy-policy .privacy-policy-actions a.privacy-policy-channel{border:1px solid #a4e5f2;background:#eafaff;color:#006b8f;text-decoration:none}
#privacy-policy .privacy-policy-actions a.privacy-policy-channel:hover{background:#d6f5fc}
#privacy-policy .privacy-policy-actions button:hover,#privacy-policy .privacy-media-stop:hover{background:#e2e8f0}
#privacy-policy .privacy-media-stop{margin:8px 0;cursor:pointer}
#privacy-policy .privacy-policy-actions svg{width:14px;height:14px;flex:none}
#privacy-policy .privacy-policy-actions>[data-privacy-policy-close]{background:#00c4f5;color:#003153;font-size:14px;font-weight:800;flex:none}
#privacy-policy .privacy-policy-actions>[data-privacy-policy-close]:hover{background:#69def9}
@keyframes privacy-enter{from{opacity:0;translate:0 8px}to{opacity:1;translate:0 0}}
@media(max-width:639px){
 #cookie-banner.cookie-banner{padding:20px;--privacy-bottom:88px}
 #cookie-banner h2{font-size:14px;line-height:20px}
 #cookie-banner.cookie-banner p.privacy-banner-copy{font-size:12px;line-height:19.5px}
 #cookie-banner.cookie-banner .privacy-choice{font-size:12px;line-height:16px;min-height:44px}
 #cookie-banner.cookie-banner [data-privacy-save]{width:100%}
 #privacy-policy{width:calc(100% - 32px);padding:24px}
 #privacy-policy h2{font-size:20px;line-height:28px}
 #privacy-policy :is(p,ul){font-size:12px;line-height:16px}
 #privacy-policy h3{font-size:14px;line-height:20px}
 #privacy-policy table{min-width:540px}
 #privacy-policy .privacy-policy-actions{align-items:stretch;flex-direction:column}
 #privacy-policy .privacy-policy-actions>[data-privacy-policy-close]{font-size:12px}
}
@media(prefers-reduced-motion:reduce){#cookie-banner.cookie-banner{animation:none}#cookie-banner *,#privacy-policy *{transition:none!important;scroll-behavior:auto}}
.form-privacy-note{font-size:12px;line-height:1.7;color:#425f73;margin:16px 0}
.form-privacy-note a{text-decoration:underline;text-underline-offset:3px}
.policy-draft-notice{border-left:3px solid #df9137;padding-left:16px;font-size:14px}
.cookie-policy-review>h2{margin:24px 0 16px}
.cookie-policy-review details{margin:20px 0;border:1px solid #cadce7;border-radius:14px;padding:0 20px;background:#fff}
.cookie-policy-review summary{padding:16px 0;min-height:48px;font-weight:700;color:#003153;cursor:pointer}
.cookie-policy-review details p,.cookie-policy-review details ul{margin:0 0 18px;font-size:14px;line-height:1.85}
.cookie-policy-review a{text-decoration:underline;text-underline-offset:3px;overflow-wrap:anywhere}
.policy-table-scroll{max-width:100%;overflow-x:auto;margin:18px 0 24px}
.policy-table-scroll table{border-collapse:collapse;min-width:780px;font-size:12px;line-height:1.6}
.policy-table-scroll :is(th,td){padding:10px;border:1px solid #cadce7;vertical-align:top;text-align:left}
.policy-table-scroll th{background:#eff7fa;color:#003153}
::view-transition-old(root),::view-transition-new(root){animation-duration:.16s}
@media(prefers-reduced-motion:reduce){::view-transition-old(root),::view-transition-new(root){animation:none!important}}
MT_NEW_CSS_END;
$css.="\n";
$css=str_replace("\r\n","\n",$css);
if(ghblob($css)!=='26a5d029893460532ac5abba3e0a76a928fc9047')throw new RuntimeException('New CSS SHA mismatch');
$cssFile=$root.'/privacy.css';
$current=file_get_contents($cssFile);
if($current===false)throw new RuntimeException('Current CSS unavailable');
$old=str_replace("\r\n","\n",$current);
if(ghblob($old)!=='0b24e5971539249a75f07f30f39262ceebd2ee0f')throw new RuntimeException('Current CSS differs from approved baseline; no changes made');
$newRaw=str_contains($current,"\r\n")?str_replace("\n","\r\n",$css):$css;
$map=[];$bundles=[];
foreach(glob($root.'/assets/site-*.css')?:[] as $f){
 $data=file_get_contents($f); if($data===false)throw new RuntimeException('Cannot read '.$f);
 $count=0;
 $newData=str_replace($current,$newRaw,$data,$count);
 if(!$count){$newData=str_replace($old,$css,$data,$count);}
 if(!$count){if(str_contains($data,'/* /privacy.css */'))throw new RuntimeException('Unexpected bundled CSS in '.basename($f));continue;}
 if($count!==1)throw new RuntimeException('Unexpected duplicate CSS in '.basename($f));
 $name=basename($f);
 $hashed=substr(hash('sha256',$newData),0,12);
 $newName=preg_replace('/[a-f0-9]{12}(?=\.css$)/',$hashed,$name,1,$replacementCount);
 if($replacementCount!==1||$newName===$name)throw new RuntimeException('Asset hash not updated: '.$name);
 $target=dirname($f).'/'.$newName;
 if(is_file($target)&&file_get_contents($target)!==$newData)throw new RuntimeException('New asset collides with existing: '.$newName);
 $map[$name]=$newName;
 $bundles[$target]=$newData;
}
if(!$map)throw new RuntimeException('Cannot locate consent CSS in compiled assets');
$pages=[];
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){
 if(!$file->isFile()||$file->isLink()||$file->getExtension()!=='html')continue;
 $path=$file->getPathname();$html=file_get_contents($path);if($html===false)throw new RuntimeException('Cannot read page: '.$path);
 $updated=str_replace(array_map(static fn($n)=>'/assets/'.$n,array_keys($map)),array_map(static fn($n)=>'/assets/'.$n,array_values($map)),$html,$count);
 if($count)$pages[$path]=['old'=>$html,'new'=>$updated];
}
if(count($pages)<5)throw new RuntimeException('Too few HTML references; no files changed');
if(in_array('--dry-run',$argv,true)){echo 'DRY_RUN: '.json_encode(['pageCount'=>count($pages),'bundleMap'=>$map],JSON_UNESCAPED_SLASHES)."\n";exit(0);}
$backup=$stage.'/backup';
if(!is_dir($backup)&&!mkdir($backup,0700,true))throw new RuntimeException('Cannot prepare backup directory');
foreach($pages as $file=>$pair){
 $relative=substr($file,strlen($root)+1);$dest=$backup.'/'.$relative;$dir=dirname($dest);
 if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new RuntimeException('Cannot create backup directory');
 if(file_put_contents($dest,$pair['old'],LOCK_EX)!==strlen($pair['old']))throw new RuntimeException('Cannot back up '.$file);
}
if(!copy($cssFile,$backup.'/privacy.css'))throw new RuntimeException('Cannot back up CSS');
$written=[];$newAssets=[];
try{
 foreach($bundles as $dest=>$data){if(!is_file($dest)){writeAtomic($dest,$data);$newAssets[]=$dest;}}
 foreach($pages as $file=>$pair){writeAtomic($file,$pair['new']);$written[]=$file;}
 writeAtomic($cssFile,$newRaw);$written[]=$cssFile;
 foreach($pages as $file=>$pair){if(file_get_contents($file)!==$pair['new'])throw new RuntimeException('Failed HTML verification');}
 if(ghblob(str_replace("\r\n","\n",file_get_contents($cssFile)))!=='26a5d029893460532ac5abba3e0a76a928fc9047')throw new RuntimeException('Final CSS mismatch');
 foreach($map as $newName){if(!is_file($root.'/assets/'.$newName))throw new RuntimeException('Missing new bundle');}
 $report=['success'=>true,'source'=>'main@c6f9f939337fde2c3c8ed57b664c180408feb59f','updated_pages'=>count($pages),'new_assets'=>count($newAssets),'map'=>$map,'backup'=>$backup,'completed_at'=>gmdate('c')];
 writeAtomic($stage.'/done.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
 echo 'PUBLISHED: '.json_encode($report,JSON_UNESCAPED_SLASHES)."\n";
}catch(Throwable $e){
 foreach(array_reverse($written) as $file){$rel=substr($file,strlen($root)+1);@copy($backup.'/'.$rel,$file);}
 foreach($newAssets as $asset)@unlink($asset);
 throw $e;
}
