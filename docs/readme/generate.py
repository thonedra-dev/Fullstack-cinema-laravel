"""Rebuild CinemaX's self-contained README artwork. Python 3, no dependencies."""
from html import escape
from pathlib import Path
import math
import re

OUT = Path(__file__).resolve().parent
BG, CARD, EDGE = '#07070f', '#0e0e1a', '#282438'
INK, MUTED, PURPLE, PINK, CYAN = '#f0eeff', '#aaa5bf', '#a855f7', '#ec4899', '#22d3ee'


def text(x, y, value, size=22, color=INK, weight=400, extra=''):
    return f'<text x="{x}" y="{y}" font-size="{size}" fill="{color}" font-weight="{weight}" {extra}>{escape(value)}</text>'


def lines(x, y, values, size=22, color=MUTED, step=31, weight=400):
    return ''.join(text(x, y + i * step, v, size, color, weight) for i, v in enumerate(values))


def rect(x, y, w, h, fill=CARD, stroke=EDGE, r=20, extra=''):
    return f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{r}" fill="{fill}" stroke="{stroke}" {extra}/>'


def path(d, color=PURPLE, width=2, extra=''):
    return f'<path d="{d}" fill="none" stroke="{color}" stroke-width="{width}" stroke-linecap="round" stroke-linejoin="round" {extra}/>'


def pill(x, y, value, color=PURPLE, width=None, size=17):
    width = width or len(value) * size * .58 + 28
    return rect(x, y, width, 34, '#151222', color, 17, 'stroke-opacity=".5"') + text(x + 14, y + 23, value, size, color, 600)


def label(x, y, value, color=PURPLE):
    return text(x, y, value, 16, color, 600, 'letter-spacing="2.8"')


def icon(kind, x, y, color=PURPLE, scale=1):
    shapes = {
        'film': '<rect x="2" y="7" width="30" height="23" rx="4"/><path d="M2 10h30M2 7l-1-6 30-5 1 6M7 0l5 5M20-2l5 5"/>',
        'seat': '<rect x="7" y="2" width="20" height="22" rx="5"/><path d="M3 15v14h28V15M7 29v5M27 29v5"/>',
        'city': '<path d="M2 31h31M5 31V10h11v21M16 31V2h14v29M9 15h3M9 21h3M21 8h4M21 14h4M21 20h4"/>',
        'calendar': '<rect x="2" y="6" width="30" height="27" rx="5"/><path d="M2 15h30M10 2v8M24 2v8M10 22h4M21 22h3M10 28h4"/>',
        'ticket': '<path d="M2 5h30v8a5 5 0 0 0 0 10v8H2v-8a5 5 0 0 0 0-10Z"/><path d="M23 8v3m0 5v3m0 5v3"/>',
        'people': '<circle cx="13" cy="9" r="6"/><path d="M2 31v-5a11 11 0 0 1 22 0v5M26 4a6 6 0 0 1 0 12M29 21a10 10 0 0 1 5 10"/>',
        'cup': '<path d="M4 12h27l-4 21H8ZM2 12l5-6h21l5 6M17 6l4-6M10 18l1 9M24 18l-1 9"/>',
        'code': '<path d="M10 7L0 17l10 10M24 7l10 10-10 10M21 2l-8 30"/>',
    }
    return f'<g transform="translate({x} {y}) scale({scale})" fill="none" stroke="{color}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{shapes[kind]}</g>'


def seat(x, y, color, scale=1, selected=False):
    cls = 'class="breathe"' if selected else ''
    return f'<g transform="translate({x} {y}) scale({scale})"><g {cls}>' + rect(3, 0, 23, 22, color, color, 5, 'fill-opacity=".17"') + path('M0 12v15h29V12M5 27v4M24 27v4', color, 2.4) + ('<path d="m9 11 4 4 7-7" fill="none" stroke="#f0eeff" stroke-width="2"/>' if selected else '') + '</g></g>'


def svg(name, w, h, title, desc, content):
    if name.endswith('-mobile'):
        content = re.sub(r'font-size="([\d.]+)"', lambda m: f'font-size="{float(m[1]) * 1.25:g}"' if float(m[1]) < 30 else m[0], content)
    defs = '''<defs>
      <linearGradient id="brand" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#a855f7"/><stop offset="1" stop-color="#ec4899"/></linearGradient>
      <linearGradient id="ice" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#22d3ee"/><stop offset="1" stop-color="#a855f7"/></linearGradient>
      <radialGradient id="halo"><stop stop-color="#7c3aed" stop-opacity=".23"/><stop offset="1" stop-color="#7c3aed" stop-opacity="0"/></radialGradient>
      <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M40 0H0v40" fill="none" stroke="#a855f7" stroke-opacity=".055"/></pattern>
      <filter id="glow" x="-70%" y="-70%" width="240%" height="240%"><feGaussianBlur stdDeviation="5"/></filter>
    </defs>
    <style>
      text{font-family:'Segoe UI',Arial,Helvetica,sans-serif} .mono{font-family:Consolas,'Liberation Mono',monospace}
      .float{animation:float 9s ease-in-out infinite} .breathe{animation:breathe 5s ease-in-out infinite}
      .signal{stroke-dasharray:5 12;animation:signal 12s linear infinite}
      @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
      @keyframes breathe{0%,100%{opacity:.65}50%{opacity:1}}
      @keyframes signal{to{stroke-dashoffset:-170}}
      @media(prefers-reduced-motion:reduce){*{animation:none!important}}
    </style>'''
    doc = f'''<svg xmlns="http://www.w3.org/2000/svg" width="{w}" height="{h}" viewBox="0 0 {w} {h}" role="img" aria-labelledby="title desc">
    <title id="title">{escape(title)}</title><desc id="desc">{escape(desc)}</desc>{defs}
    <defs><clipPath id="panel"><rect width="{w}" height="{h}" rx="24"/></clipPath></defs>
    {rect(.5,.5,w-1,h-1,BG,EDGE,24)}<g clip-path="url(#panel)">{content}</g></svg>'''
    (OUT / f'{name}.svg').write_text(doc, encoding='utf-8')


def aperture(x, y, s=1):
    frames = ''
    for i in range(12):
        size = 280 * (.936 ** i)
        cx, cy = 15 - i * 5, -i * 6
        angle = math.radians(20 + i * 3.4)
        points = []
        for px, py in [(-size/2,-size/2),(size/2,-size/2),(size/2,size/2),(-size/2,size/2)]:
            rx, ry = px * math.cos(angle) - py * math.sin(angle), px * math.sin(angle) + py * math.cos(angle)
            points.append((cx + rx + ry * .28, cy + ry * .83))
        d = 'M' + ' L'.join(f'{px:.1f} {py:.1f}' for px,py in points) + 'Z'
        color = PURPLE if i % 3 else PINK
        frames += path(d, color, 2.7, f'stroke-opacity="{.92-i*.055}"')
    ray = path('M-168 144 212-151',CYAN,.8,'stroke-opacity=".28" class="signal"')
    return f'<g transform="translate({x} {y}) scale({s})"><ellipse cy="190" rx="160" ry="17" fill="#a855f7" opacity=".09" filter="url(#glow)"/><g class="float">{frames}{ray}</g></g>'


def hero(mobile=False):
    w,h = (720,1000) if mobile else (1200,630)
    c = f'<rect width="{w}" height="{h}" rx="24" fill="url(#grid)"/>'
    c += f'<ellipse cx="{w*.73}" cy="{h*.45}" rx="400" ry="340" fill="url(#halo)"/>'
    c += icon('film',48,45,PURPLE,.8) + label(91,64,'CINEMAX /',INK)
    c += label(48,126,'CINEMA BOOKING SYSTEM',MUTED)
    c += f'<text x="44" y="230" font-size="{100 if mobile else 110}" fill="{INK}" font-weight="700" letter-spacing="-6">Cinema<tspan fill="url(#brand)">X</tspan></text>'
    c += text(48,291,'The night starts here.',34 if mobile else 36,INK,400,'letter-spacing="-1"')
    c += lines(48,340,['A seat for every story.','A control room for every screen.'],22,MUTED,32)
    c += pill(48,407,'BOOKING × CINEMA OPERATIONS',PINK,370)
    ax,ay,scale = (360,674,1.03) if mobile else (925,285,1.06)
    c += aperture(ax,ay,scale)
    tx,ty = (403,720) if mobile else (937,352)
    c += f'<g transform="translate({tx} {ty}) rotate(-9)"><g class="float">'
    c += rect(-90,-55,226,143,'#151022','#76518f',16)
    c += '<path d="M62-48V80" stroke="#76518f" stroke-dasharray="4 6"/>'
    c += text(-72,-26,'ADMIT ONE',12,PINK,700,'letter-spacing="2"') + text(-72,9,'CinemaX',26,INK,700)
    c += text(-72,52,'ENJOY THE SHOW',10,MUTED,600,'letter-spacing="1.5"') + icon('seat',82,-18,CYAN,.9)
    c += '</g></g>'
    baseline = 917 if mobile else 536
    c += path(f'M48 {baseline-24}H{w-48}',EDGE,1)
    if mobile:
        c += label(48,baseline+10,'LARAVEL 13 / PHP 8.3+',MUTED)
        c += label(48,baseline+45,'BLADE / VITE 8 / TAILWIND 4',MUTED)
    else:
        for xx,caption,col in [(48,'LARAVEL 13',INK),(248,'PHP 8.3+',MUTED),(428,'BLADE',MUTED),(562,'VITE 8',MUTED),(705,'TAILWIND 4',MUTED),(1038,'VOL. 01',PINK)]: c += label(xx,baseline+13,caption,col)
        for xx,caption in [(48,'MOVIE DISCOVERY'),(302,'SEAT RESERVATIONS'),(590,'SHOWTIME PLANNING'),(892,'FINANCE EXPLORER')]: c += text(xx,593,caption,14,MUTED,500,'letter-spacing="2"')
    svg('hero-mobile' if mobile else 'hero',w,h,'CinemaX — The night starts here.','A Laravel cinema booking and operations project. Violet and pink aperture artwork inspired by the admin login. Laravel 13, PHP 8.3+, Blade, Vite 8 and Tailwind CSS 4.',c)


def discover(x,y,w,h):
    c = rect(x,y,w,h) + label(x+26,y+35,'01 / DISCOVER',CYAN)
    px,py,pw = x+26,y+59,w-52
    c += rect(px,py,pw,210,'#131020','#302642',12)
    cx,cy = px+pw*.58,py+78
    c += f'<ellipse cx="{cx}" cy="{cy}" rx="120" ry="100" fill="url(#halo)"/>'
    c += f'<circle cx="{cx}" cy="{cy}" r="48" fill="#0a0914" stroke="url(#brand)" stroke-width="4"/><circle cx="{cx}" cy="{cy}" r="51" fill="none" stroke="#ec4899" stroke-width="5" opacity=".3" filter="url(#glow)"/>'
    for i in range(6): c += path(f'M{px+14} {py+160+i*7}Q{cx} {py+122+i*11} {px+pw-14} {py+156+i*7}',PURPLE,.8,f'stroke-opacity="{.5-i*.06}"')
    c += text(px+18,py+184,'AFTERLIGHT',22,INK,600,'letter-spacing="4"')
    c += f'<circle cx="{px+pw-32}" cy="{py+28}" r="15" fill="#07070f" stroke="#645478"/><path d="m{px+pw-35} {py+22} 9 6-9 6Z" fill="#f0eeff"/>'
    c += text(x+26,y+312,'Find your next story.',25,INK,600)
    return c + lines(x+26,y+349,['Movie posters, trailers, genres,','cinemas and screening times.'],20,MUTED,29)


def seating(x,y,w,h):
    c = rect(x,y,w,h) + label(x+26,y+35,'02 / CHOOSE',PURPLE)
    c += path(f'M{x+45} {y+79}Q{x+w/2} {y+58} {x+w-45} {y+79}','url(#brand)',3)
    c += text(x+w/2,y+105,'SCREEN',12,MUTED,500,'text-anchor="middle" letter-spacing="4"')
    for row in range(4):
        for col in range(6):
            sx = x + (w-260)/2 + col*41 + (14 if col>=3 else 0)
            color = ['#22c55e','#6366f1',PINK,'#f43f5e'][row]
            if row==1 and col in (2,3): color=PURPLE
            if row==2 and col in (0,1): color='#f97316'
            c += seat(sx,y+123+row*35,color,.8,row==1 and col in (2,3))
    c += text(x+26,y+312,'Make the seat yours.',25,INK,600)
    return c + lines(x+26,y+349,['Standard · Premium · Family · Couple','Weekday and weekend pricing.'],19 if w<400 else 21,MUTED,29)


def booking(x,y,w,h):
    c = rect(x,y,w,h) + label(x+26,y+35,'03 / MAKE IT A NIGHT',PINK)
    c += rect(x+26,y+63,w-52,195,'#151020','#4d3154',12)
    c += icon('ticket',x+46,y+84,PINK,.8) + text(x+85,y+108,'YOUR MOVIE NIGHT',17,INK,600)
    c += path(f'M{x+43} {y+126}H{x+w-43}','#4d3154',1,'stroke-dasharray="4 5"')
    c += text(x+45,y+162,'05:00',35,INK,600,'class="mono"') + text(x+45,y+192,'Seat hold window',18,MUTED)
    c += pill(x+45,y+210,'STRIPE → CONFIRMATION',CYAN,w-90,14)
    c += text(x+26,y+312,'From hold to confirmed.',25,INK,600)
    return c + lines(x+26,y+349,['Card checkout, booking confirmation,','and automatic expired-hold cleanup.'],19 if w<400 else 21,MUTED,29)


def experience(mobile=False):
    w,h = (720,1630) if mobile else (1200,685)
    c = label(48,54,'THE CUSTOMER EXPERIENCE',CYAN)
    c += text(48,112,'Front row. Full journey.',43 if mobile else 47,INK,600,'letter-spacing="-1.5"')
    c += text(48,151,'From the first trailer to the final confirmation.',21,MUTED)
    if mobile:
        for fn,y in [(discover,190),(seating,634),(booking,1078)]: c += fn(48,y,624,414)
        c += lines(48,1551,['Email verification + Google sign-in.','Original interface illustrations; not screenshots.'],18,MUTED,28)
    else:
        for fn,x in [(discover,32),(seating,418),(booking,804)]: c += fn(x,190,364,410)
        c += text(48,646,'EMAIL VERIFICATION + GOOGLE SIGN-IN',16,CYAN,500,'letter-spacing="1.3"')
        c += text(1152,646,'Original interface illustrations · not screenshots',16,MUTED,400,'text-anchor="end"')
    svg('experience-mobile' if mobile else 'experience',w,h,'The CinemaX customer journey','Discover posters, trailers, genres and showtimes. Choose Standard, Premium, Family or Couple seats with weekday/weekend pricing. Five-minute booking holds lead to Stripe card payment and confirmation. Email verification and Google sign-in need integration setup. The illustrations are conceptual, not application screenshots.',c)


def admin(x,y,w,h):
    c = rect(x,y,w,h,'#100d1d','#43305c') + label(x+28,y+38,'SUPERVISOR / ADMIN',PURPLE)
    c += text(x+28,y+85,'The control room.',32,INK,600)
    tiles = [('city','Cinemas & halls'),('film','Movies & pricing'),('people','Manager allocation'),('cup','F&B catalogue')]
    tw = (w-72)/2
    for i,(kind,title) in enumerate(tiles):
        tx,ty = x+28+(i%2)*(tw+16),y+113+(i//2)*100
        c += rect(tx,ty,tw,84,'#171226','#302443',12)
        c += icon(kind,tx+14,ty+14,PURPLE,.65) + text(tx+14,ty+64,title,18,INK,500)
    c += path(f'M{x+28} {y+331}H{x+w-28}',EDGE,1)
    c += text(x+28,y+368,'Financial drilldown',24,INK,600)
    c += lines(x+28,y+405,['Cinema → theatre → showtime','Confirmed tickets, payments and seat status.'],19,MUTED,29)
    c += path(f'M{x+28} {y+464}h{w-56}',PURPLE,2,'class="signal"')
    for nx in (x+28,x+w/2,x+w-28): c += f'<circle cx="{nx}" cy="{y+464}" r="5" fill="#a855f7"/>'
    return c


def manager(x,y,w,h):
    c = rect(x,y,w,h) + label(x+26,y+36,'BRANCH MANAGER',PINK)
    c += text(x+26,y+76,'Plan the next screening.',27,INK,600)
    c += lines(x+26,y+111,['Quotas, hall schedules and overlap checks.','Proposal review, revisions and notifications.'],20,MUTED,29)
    for i,(caption,col) in enumerate([('Plan',PURPLE),('Review',PINK),('Live',CYAN)]):
        xx=x+28+i*(w-56)/3
        c += rect(xx,y+168,(w-80)/3,39,'#171226',col,8) + text(xx+13,y+194,caption,18,col,500)
    return c


def monitor(x,y,w,h):
    c = rect(x,y,w,h) + label(x+26,y+36,'SEAT MONITOR',CYAN)
    c += text(x+26,y+77,'Keep an eye on the house.',27,INK,600)
    c += text(x+26,y+113,'Supervisor + manager access.',20,MUTED)
    for i,(caption,col) in enumerate([('Available','#22c55e'),('Held','#f97316'),('Sold','#ef4444')]):
        xx=x+28+i*(w-56)/3
        c += seat(xx,y+150,col,.72) + text(xx+35,y+171,caption,18,col)
    return c


def operations(mobile=False):
    w,h = (720,1420) if mobile else (1200,828)
    c = label(48,54,'BEHIND THE SCREENS',PINK)
    c += lines(48,112,['One cinema.','Every moving part.'] if mobile else ['One cinema. Every moving part.'],43 if mobile else 47,INK,55,600)
    if mobile:
        c += admin(48,210,624,503) + manager(48,737,624,234) + monitor(48,995,624,221)
        c += label(48,1263,'THE RELEASE WORKFLOW',MUTED)
        c += lines(48,1303,['Allocate → plan → review → screen','Rejected proposals return for revision.'],23,INK,34)
    else:
        c += admin(32,160,548,503) + manager(604,160,564,234) + monitor(604,418,564,245)
        c += label(48,712,'THE RELEASE WORKFLOW',MUTED)
        for i,(title,col) in enumerate([('Allocate',PURPLE),('Plan',PINK),('Review',PINK),('Screen',CYAN)]):
            xx=48+i*282
            c += text(xx,757,f'0{i+1}',18,col,500,'class="mono"') + text(xx+38,757,title,25,INK,500)
            if i<3: c += path(f'M{xx+172} 750h64m-7-5 7 5-7 5','#716681',1.5,'class="signal"')
        c += text(48,799,'Rejected proposals return to the branch manager for revision.',18,MUTED)
    svg('operations-mobile' if mobile else 'operations',w,h,'Cinema operations, from allocation to screening','Supervisors manage cinemas, halls, services, movies, seat pricing, manager allocation, F&B catalogue and finance. Branch managers schedule films, check overlaps and submit proposals for approval or revision. Seat monitoring currently admits supervisors and managers.',c)


def stack(x,y,w):
    c = label(x,y,'THE APPLICATION',CYAN)
    rows=[('01','PRESENTATION','Blade · page CSS · vanilla JavaScript'),('02','REQUEST LAYER','Web routes · controllers · session guards'),('03','DATA & AUTOMATION','Eloquent · relational DB · Laravel scheduler')]
    for i,(num,title,body) in enumerate(rows):
        yy=y+21+i*103
        c += rect(x,yy,w,84,'#10101b',EDGE,12)
        c += text(x+17,yy+32,num,18,PURPLE,500,'class="mono"') + text(x+58,yy+31,title,15,INK,600,'letter-spacing="1.5"')
        c += text(x+17,yy+64,body,18,MUTED)
        if i<2: c+=path(f'M{x+w/2} {yy+85}v16',PURPLE,1.5,'class="signal"')
    return c


def data_model(x,y,w):
    c = label(x,y,'THE DOMAIN',PINK) + rect(x,y+21,w,290,'#100d1b','#3c2b50',16)
    c += text(x+24,y+61,'Cinema + theatre type',25,INK,600)
    c += path(f'M{x+37} {y+78}v36h17',PURPLE,2)
    c += text(x+66,y+119,'Hall',26,PURPLE,600) + text(x+138,y+119,'→ Showtime',26,INK,600)
    c += text(x+24,y+170,'Booking → tickets + payment',23,INK,500)
    return c + lines(x+24,y+210,['Seats belong to theatre templates.','Tickets link seats to a specific showtime.'],19,MUTED,29)


def architecture(mobile=False):
    w,h = (720,1010) if mobile else (1200,610)
    c = label(48,54,'THE ENGINE',CYAN) + text(48,112,'Built around the show.',43 if mobile else 47,INK,600,'letter-spacing="-1.5"')
    if mobile:
        c += stack(48,169,624) + data_model(48,535,624)
        c += lines(48,910,['SESSION GUARDS','customer · supervisor · manager · web (employee)'],20,MUTED,32)
    else:
        c += stack(48,174,536) + data_model(616,174,536)
        c += path('M48 517H1152',EDGE,1) + label(48,551,'SESSION GUARDS',PURPLE)
        c += text(300,551,'customer  /  supervisor  /  manager  /  web (employee)',21,INK)
        c += text(48,586,'app/Http/Controllers  ·  app/Models  ·  resources/views  ·  database/migrations  ·  routes/web.php',17,MUTED,400,'class="mono"')
    svg('architecture-mobile' if mobile else 'architecture',w,h,'CinemaX architecture','A Laravel monolith with Blade views, page CSS and vanilla JavaScript, web routes, controllers, four session guards, Eloquent and scheduled booking cleanup. A hall joins a cinema to a reusable theatre type. Showtimes belong to halls. Tickets connect a booking, showtime, seat and price; bookings have payments.',c)


def launch(mobile=False):
    w,h = (720,298) if mobile else (1200,215)
    c = f'<ellipse cx="{w-70}" cy="100" rx="330" ry="250" fill="url(#halo)"/>' + label(48,50,'YOUR LOCAL PREMIERE',PINK)
    c += text(48,105,'Bring the house lights down.',37 if mobile else 45,INK,600,'letter-spacing="-1.1"')
    c += text(48,148,'Install. Configure. Open the doors.',22,MUTED)
    if mobile: c += lines(48,210,['PHP 8.3+ · Composer 2','Node 20.19+ or 22.12+ · npm · SQLite / MySQL'],20,INK,33)
    else:
        c += text(48,187,'PHP 8.3+  ·  COMPOSER 2  ·  NODE 20.19+ / 22.12+  ·  NPM  ·  SQLITE / MYSQL',16,INK,500,'letter-spacing="1.1"')
        c += icon('code',1091,72,PURPLE,1.3)
    svg('launch-mobile' if mobile else 'launch',w,h,'Local setup — your local premiere','Prerequisites: PHP 8.3+, Composer 2, Node 20.19+ or 22.12+, npm, and SQLite or MySQL. Expand setup below; current repository gaps are documented.',c)


def footer(mobile=False):
    c = icon('film',42,35,PURPLE,.75) + text(84,58,'CinemaX',32 if mobile else 26,INK,600)
    if mobile: c += text(42,112,'LIGHTS DOWN. POSSIBILITIES UP.',18,MUTED,500,'letter-spacing="2.4"')
    else: c += text(1154,54,'LIGHTS DOWN. POSSIBILITIES UP.',17,MUTED,500,'text-anchor="end" letter-spacing="2.4"')
    svg('footer-mobile' if mobile else 'footer',720 if mobile else 1200,150 if mobile else 100,'CinemaX — Lights down. Possibilities up.','CinemaX cinema booking and operations project.',c)


if __name__ == '__main__':
    for render in (hero,experience,operations,architecture,launch):
        render()
        render(mobile=True)
    footer()
    footer(mobile=True)
    print('Generated 12 self-contained SVG assets.')
