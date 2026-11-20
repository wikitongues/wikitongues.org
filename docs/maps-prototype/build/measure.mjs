import fs from 'node:fs'; import * as d3 from 'd3-geo';
const S = process.argv[2];
const cs = JSON.parse(fs.readFileSync(`${S}/ne/ne_50m_admin_0_countries.geojson`)).features;
const get = (a3) => cs.find((f) => f.properties.ADM0_A3 === a3);
const st = (proj, lon, lat) => { const e = 1e-4, cl = Math.cos(lat * Math.PI / 180);
  const p0 = proj([lon, lat]), pe = proj([lon + e / cl, lat]), pn = proj([lon, lat + e]); if (!p0||!pe||!pn) return null;
  const a = [pe[0]-p0[0], pe[1]-p0[1]], b = [pn[0]-p0[0], pn[1]-p0[1]];
  const E=a[0]**2+a[1]**2, G=b[0]**2+b[1]**2, F=a[0]*b[0]+a[1]*b[1], tr=E+G, det=Math.sqrt(Math.max(0,E*G-F*F));
  const s1=Math.sqrt((tr+Math.sqrt(Math.max(0,tr*tr-4*det*det)))/2); return (s1/(det/s1)-1)*100; };
const PAD=[[48,36],[352,264]];
for (const [name, a3] of [['Russia','RUS'],['United States','USA'],['China','CHN'],['India','IND'],['Canada','CAN'],['Brazil','BRA'],['Australia','AUS']]) {
  const f = get(a3), c = d3.geoCentroid(f), [[, s], [, n]] = d3.geoBounds(f), span = n - s;
  const az = d3.geoAzimuthalEqualArea().rotate([-c[0], -c[1]]).fitExtent(PAD, f);
  const co = d3.geoConicEqualArea().parallels([s+span/6, n-span/6]).rotate([-c[0],0]).center([0,(s+n)/2]).fitExtent(PAD, f);
  const pts = []; d3.geoStream(f, { point(x,y){ pts.push([x,y]); }, lineStart(){}, lineEnd(){}, polygonStart(){}, polygonEnd(){}, sphere(){} });
  const stats = (p) => { const v = pts.filter((_,i)=>i%7===0).map(([x,y])=>st(p,x,y)).filter((x)=>x!=null).sort((a,b)=>a-b);
    return `median ${v[v.length>>1].toFixed(1).padStart(5)}%  worst ${v[v.length-1].toFixed(1).padStart(6)}%`; };
  console.log(name.padEnd(14), 'azimuthal', stats(az), ' | conic', stats(co));
}
