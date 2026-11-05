import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const LOGIN='Sandro94';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const stamp=new Date().toISOString().slice(0,10),display=stamp.split('-').reverse().join('/');
const esc=s=>String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&apos;');
const number=n=>new Intl.NumberFormat('pt-BR').format(n);
async function api(endpoint){
 const headers={'User-Agent':'Sandro94-profile','Accept':'application/vnd.github+json','X-GitHub-Api-Version':'2022-11-28'};
 if(process.env.GITHUB_TOKEN)headers.Authorization='Bearer '+process.env.GITHUB_TOKEN;
 const response=await fetch('https://api.github.com'+endpoint,{headers,redirect:'error',signal:AbortSignal.timeout(25000)});
 if(!response.ok)throw Error('GitHub API indisponível: HTTP '+response.status);
 return response.json();
}
const user=await api('/users/'+LOGIN);
if(user.login.toLowerCase()!==LOGIN.toLowerCase())throw Error('Perfil inesperado');
const repositories=[];
for(let page=1;page<=100;page++){
 const batch=await api('/users/'+LOGIN+'/repos?type=owner&per_page=100&page='+page);
 if(!Array.isArray(batch))throw Error('Lista pública inválida');
 repositories.push(...batch.filter(r=>r.private===false&&r.owner.login.toLowerCase()===LOGIN.toLowerCase()));
 if(batch.length<100)break;
 if(page===100)throw Error('Paginação excedida');
}
const languageBytes=new Map();
for(const repo of repositories.filter(r=>!r.fork&&r.name.toLowerCase()!==LOGIN.toLowerCase())){
 const languages=await api('/repos/'+LOGIN+'/'+encodeURIComponent(repo.name)+'/languages');
 for(const [language,bytes]of Object.entries(languages))if(Number.isFinite(bytes)&&bytes>0)languageBytes.set(language,(languageBytes.get(language)||0)+bytes);
}
// Always anonymous: use the same contribution calendar a public visitor sees.
const calendar=await fetch('https://github.com/users/'+LOGIN+'/contributions',{headers:{'User-Agent':'Sandro94-profile','Accept-Language':'en'},redirect:'error',signal:AbortSignal.timeout(25000)});
if(!calendar.ok)throw Error('Calendário público indisponível: HTTP '+calendar.status);
const html=await calendar.text(),plain=html.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ');
const totalMatch=plain.match(/([\d,]+)\s+contributions?\s+in the last year/i);
if(!totalMatch)throw Error('Formato do calendário mudou; os cartões anteriores serão preservados');
const totalContributions=Number(totalMatch[1].replaceAll(',',''));
const attr=(tag,name)=>new RegExp('\\b'+name+'="([^"]*)"').exec(tag)?.[1];
const days=[...html.matchAll(/<td\b[^>]*\bdata-date="[^"]+"[^>]*>/g)].map(([tag])=>({date:attr(tag,'data-date'),level:Number(attr(tag,'data-level'))})).filter(d=>/^\d{4}-\d{2}-\d{2}$/.test(d.date)&&d.date<=stamp&&Number.isInteger(d.level)&&d.level>=0&&d.level<=4).sort((a,b)=>a.date.localeCompare(b.date));
if(!days.length||!Number.isFinite(totalContributions))throw Error('Calendário público sem dados válidos');
const frame=(title,height,content,summary)=>`<svg xmlns="http://www.w3.org/2000/svg" width="680" height="${height}" viewBox="0 0 680 ${height}" role="img" aria-labelledby="title desc"><title id="title">${esc(title)}</title><desc id="desc">${esc(summary)}</desc><rect x="1" y="1" width="678" height="${height-2}" rx="18" fill="#06283e" stroke="#23516c"/><g font-family="Verdana,DejaVu Sans,Arial,sans-serif"><text x="26" y="42" fill="#fff" font-size="26" font-weight="700">${esc(title)}</text><rect x="26" y="56" width="60" height="3" rx="1.5" fill="#DF9137"/>${content}<text x="26" y="${height-18}" fill="#a9c4d4" font-size="16">Dados públicos · ${esc(display)} · @${LOGIN}</text></g></svg>\n`;
const stats=[['Repositórios',user.public_repos],['Seguidores',user.followers],['Estrelas',repositories.filter(r=>!r.fork).reduce((sum,r)=>sum+r.stargazers_count,0)]];
const statsSvg=frame('GitHub em números',212,stats.map(([label,value],i)=>`<text x="${26+i*220}" y="125" fill="${i===0?'#00C4F5':'#fff'}" font-size="48" font-weight="700">${esc(number(value))}</text><text x="${26+i*220}" y="156" fill="#d7e7ef" font-size="21">${esc(label)}</text>`).join(''),stats.map(([k,v])=>k+': '+v).join('; '));
const ranked=[...languageBytes].sort((a,b)=>b[1]-a[1]),sum=ranked.reduce((n,[,bytes])=>n+bytes,0),palette=['#00C4F5','#DF9137','#8bd5e8','#eacda8','#8faebf','#5a7d94'];
let languagesSvg;
if(!sum){
 languagesSvg=frame('Linguagens mais utilizadas',218,'<text x="26" y="104" fill="#fff" font-size="23">Ainda não há um ranking público disponível.</text><text x="26" y="140" fill="#b9d1de" font-size="20">Projetos privados não entram neste gráfico.</text><text x="26" y="171" fill="#00C4F5" font-size="19">Veja a stack verificada na apresentação acima.</text>','Nenhum dado público de linguagens disponível. A stack real do projeto principal está documentada no README.');
}else{
 const rows=ranked.length>6?[...ranked.slice(0,5),['Outras',ranked.slice(5).reduce((n,[,b])=>n+b,0)]]:ranked;
 const content=rows.map(([language,bytes],i)=>{const pct=bytes/sum*100,y=91+i*43;return `<circle cx="33" cy="${y-7}" r="6" fill="${palette[i]}"/><text x="49" y="${y}" fill="#fff" font-size="22">${esc(language)}</text><rect x="273" y="${y-17}" width="280" height="12" rx="6" fill="#173f56"/><rect x="273" y="${y-17}" width="${(280*pct/100).toFixed(2)}" height="12" rx="6" fill="${palette[i]}"/><text x="574" y="${y}" fill="#d7e7ef" font-size="20">${pct.toFixed(1).replace('.',',')}%</text>`}).join('');
 languagesSvg=frame('Linguagens mais utilizadas',112+rows.length*43,content,rows.map(([l,b])=>l+': '+(b/sum*100).toFixed(1)+'%').join('; ')+'. Por bytes de código público, excluindo forks e este perfil.');
}
const first=new Date(days[0].date+'T00:00:00Z'),start=first.getTime()-first.getUTCDay()*864e5;
const weeks=Math.floor((new Date(days.at(-1).date+'T00:00:00Z').getTime()-start)/(7*864e5))+1,stride=Math.min(12,628/weeks),cell=Math.max(6,stride-3);
const colors=['#173e55','#155e75','#0a8ca6','#00C4F5','#DF9137'];
const cells=days.map(d=>{const date=new Date(d.date+'T00:00:00Z'),week=Math.floor((date.getTime()-start)/(7*864e5));return `<rect x="${(26+week*stride).toFixed(2)}" y="${124+date.getUTCDay()*12}" width="${cell.toFixed(2)}" height="9" rx="2" fill="${colors[d.level]}"/>`}).join('');
const months=['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];let previousMonth=-1;
const monthLabels=days.filter(d=>new Date(d.date+'T00:00:00Z').getUTCDay()===0).map(d=>{const date=new Date(d.date+'T00:00:00Z'),m=date.getUTCMonth();if(m===previousMonth)return '';previousMonth=m;const week=Math.floor((date.getTime()-start)/(7*864e5));return `<text x="${(26+week*stride).toFixed(2)}" y="111" fill="#a9c4d4" font-size="15">${months[m]}</text>`}).join('');
const contributionsSvg=frame('Contribuições no GitHub',260,`<text x="26" y="88" fill="#00C4F5" font-size="25" font-weight="700">${number(totalContributions)} contribuições no último ano</text>${monthLabels}${cells}<text x="26" y="224" fill="#b9d1de" font-size="16">Calendário visível no perfil público · sem detalhes privados</text>`,totalContributions+' contribuições no período do calendário público do GitHub. '+days[0].date+' a '+days.at(-1).date+'.');
const outputs={'github-stats.svg':statsSvg,'languages.svg':languagesSvg,'contributions.svg':contributionsSvg};
fs.mkdirSync(path.join(root,'assets'),{recursive:true});
for(const [name,svg]of Object.entries(outputs)){const dest=path.join(root,'assets',name),temp=dest+'.tmp';fs.writeFileSync(temp,svg,'utf8');fs.renameSync(temp,dest)}
console.log(JSON.stringify({login:LOGIN,updated:stamp,publicRepositories:user.public_repos,languages:ranked.map(([name])=>name),contributionDays:days.length,totalContributions,files:Object.keys(outputs)}));