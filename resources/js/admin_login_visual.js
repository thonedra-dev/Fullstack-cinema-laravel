/** A small, self-contained WebGL sculpture. No assets or rendering dependency. */
export function initCinemaAperture() {
    const canvas = document.getElementById('cinema-aperture');
    const panel = canvas?.closest('.login-right');
    if (!canvas || !panel) return;

    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const desktop = window.matchMedia('(min-width: 901px)');
    let gl;
    try {
        gl = canvas.getContext('webgl', { alpha: true, antialias: true, powerPreference: 'low-power' });
    } catch {
        return; // The CSS sculpture is also the no-WebGL fallback.
    }
    if (!gl) return;

    const vertexSource = `
        precision mediump float;
        attribute vec3 aPosition;
        attribute vec3 aNormal;
        attribute float aFrame;
        uniform float uTime;
        uniform float uAspect;
        uniform vec2 uPointer;
        varying vec3 vPosition;
        varying vec3 vNormal;
        varying float vFrame;

        mat3 rotateX(float a) {
            float c = cos(a), s = sin(a);
            return mat3(1., 0., 0., 0., c, s, 0., -s, c);
        }
        mat3 rotateY(float a) {
            float c = cos(a), s = sin(a);
            return mat3(c, 0., -s, 0., 1., 0., s, 0., c);
        }
        mat3 rotateZ(float a) {
            float c = cos(a), s = sin(a);
            return mat3(c, s, 0., -s, c, 0., 0., 0., 1.);
        }
        void main() {
            float phase = uTime * .18;
            mat3 local = rotateZ(aFrame * .105 + sin(phase - aFrame * .18) * .055);
            mat3 world = rotateZ(-.36 + sin(phase * .6) * .035)
                * rotateY(-.46 + sin(phase * .8) * .12 + uPointer.x * .15)
                * rotateX(.32 + cos(phase * .7) * .07 + uPointer.y * .12);
            vec3 p = local * aPosition * pow(.935, aFrame);
            p.z -= aFrame * .185;
            p = world * (p + vec3(0., 0., .65));
            p.y += .18 + sin(phase * .9) * .06;
            vPosition = p;
            vNormal = world * local * aNormal;
            vFrame = aFrame;
            float depth = 7.8 - p.z;
            gl_Position = vec4(p.x * 2.65 / uAspect, p.y * 2.65, depth - .2, depth);
        }
    `;

    const fragmentSource = `
        precision mediump float;
        varying vec3 vPosition;
        varying vec3 vNormal;
        varying float vFrame;
        uniform float uTime;

        void main() {
            vec3 n = normalize(vNormal);
            vec3 view = normalize(vec3(0., 0., 7.8) - vPosition);
            vec3 key = normalize(vec3(-3.5, 5., 5.) - vPosition);
            vec3 violet = normalize(vec3(4., -.8, 2.5) - vPosition);
            vec3 reflected = reflect(-view, n);
            float diffuse = max(dot(n, key), 0.);
            float fill = max(dot(n, violet), 0.);
            float fresnel = pow(1. - max(dot(n, view), 0.), 3.);

            // Broad studio softboxes reflected in satin metal, with narrow polished bevels.
            float softbox = pow(max(dot(reflected, normalize(vec3(-.6, 1., 1.2))), 0.), 13.);
            float strip = pow(max(dot(reflected, normalize(vec3(1., .2, .8))), 0.), 35.);
            float highlight = pow(max(dot(n, normalize(key + view)), 0.), 75.);
            float lowerLight = pow(max(dot(reflected, normalize(vec3(.4, -1., .5))), 0.), 9.);
            float silverLight = pow(max(dot(reflected, normalize(vec3(-.8, -.5, .65))), 0.), 18.);
            float shade = 1. - vFrame * .045;
            vec3 metal = mix(vec3(.105, .078, .15), vec3(.3, .24, .36), diffuse);
            vec3 color = metal * (.35 + diffuse * .8) * shade;
            color += vec3(.78, .68, .95) * softbox * .83;
            color += vec3(.74, .67, .89) * silverLight * .65;
            color += vec3(.59, .24, .98) * (strip * .9 + fill * .15);
            color += vec3(.94, .88, 1.) * highlight * .7;
            color += vec3(.34, .075, .74) * lowerLight * .4;
            color += vec3(.39, .22, .58) * fresnel * .28;
            // A very slow light passage gives the inner edges a cinematic reveal.
            float passage = pow(.5 + .5 * sin(vFrame * .48 - uTime * .28), 5.);
            color += vec3(.14, .055, .24) * passage * fresnel;
            color = color / (color + vec3(.65));
            color = pow(color, vec3(.85));
            gl_FragColor = vec4(color, 1.);
        }
    `;

    const shaders = [];
    let program;
    let buffer;
    let frameRequest = 0;
    let disposed = false;

    function release() {
        if (buffer) gl.deleteBuffer(buffer);
        if (program) gl.deleteProgram(program);
        shaders.forEach(shader => gl.deleteShader(shader));
    }

    try {
        program = gl.createProgram();
        for (const [type, source] of [[gl.VERTEX_SHADER, vertexSource], [gl.FRAGMENT_SHADER, fragmentSource]]) {
            const shader = gl.createShader(type);
            shaders.push(shader);
            gl.shaderSource(shader, source);
            gl.compileShader(shader);
            if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) throw new Error('Aperture shader unavailable');
            gl.attachShader(program, shader);
        }
        gl.linkProgram(program);
        if (!gl.getProgramParameter(program, gl.LINK_STATUS)) throw new Error('Aperture renderer unavailable');
        gl.useProgram(program);

        // Extrude a bevelled cross-section around a rounded rectangle.
        // Each face has its own normal so broad satin faces meet crisp polished edges.
        const vertices = [];
        const perimeter = [];
        const quarter = Math.PI / 2;
        for (let corner = 0; corner < 4; corner++) {
            const mid = (corner + .5) * quarter;
            const cx = Math.sign(Math.cos(mid)) * 1.04;
            const cy = Math.sign(Math.sin(mid)) * 1.2;
            for (let step = 0; step <= 28; step++) {
                const a = corner * quarter + step / 28 * quarter;
                const nx = Math.cos(a), ny = Math.sin(a);
                perimeter.push([cx + nx * .48, cy + ny * .48, nx, ny]);
            }
        }
        const profile = [
            [-.067, .07], [.067, .07], [.095, .042], [.095, -.042],
            [.067, -.07], [-.067, -.07], [-.095, -.042], [-.095, .042],
        ];
        function vertex(point, section, normal, frame) {
            const [x, y, nx, ny] = point;
            vertices.push(x + nx * section[0], y + ny * section[0], section[1],
                nx * normal[0], ny * normal[0], normal[1], frame);
        }
        for (let frame = 0; frame < 10; frame++) {
            for (let edge = 0; edge < perimeter.length; edge++) {
                const a = perimeter[edge], b = perimeter[(edge + 1) % perimeter.length];
                for (let side = 0; side < profile.length; side++) {
                    const p = profile[side], q = profile[(side + 1) % profile.length];
                    const dx = q[0] - p[0], dz = q[1] - p[1];
                    const length = Math.hypot(dx, dz);
                    const normal = [-dz / length, dx / length];
                    vertex(a, p, normal, frame); vertex(b, p, normal, frame); vertex(b, q, normal, frame);
                    vertex(a, p, normal, frame); vertex(b, q, normal, frame); vertex(a, q, normal, frame);
                }
            }
        }
        buffer = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(vertices), gl.STATIC_DRAW);
        for (const [name, size, offset] of [['aPosition', 3, 0], ['aNormal', 3, 12], ['aFrame', 1, 24]]) {
            const location = gl.getAttribLocation(program, name);
            gl.enableVertexAttribArray(location);
            gl.vertexAttribPointer(location, size, gl.FLOAT, false, 28, offset);
        }
        gl.enable(gl.DEPTH_TEST);
        gl.clearColor(0, 0, 0, 0);

        const uniforms = Object.fromEntries(['uTime', 'uAspect', 'uPointer'].map(name => [name, gl.getUniformLocation(program, name)]));
        const vertexCount = vertices.length / 7;
        const pointer = { x: 0, y: 0, targetX: 0, targetY: 0 };
        let elapsed = 0;
        let lastTime = 0;
        let lastDraw = 0;
        let width = 0;
        let height = 0;

        function draw() {
            if (!width || !height || disposed) return;
            gl.viewport(0, 0, canvas.width, canvas.height);
            gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);
            gl.uniform1f(uniforms.uTime, elapsed);
            gl.uniform1f(uniforms.uAspect, width / height);
            gl.uniform2f(uniforms.uPointer, pointer.x, pointer.y);
            gl.drawArrays(gl.TRIANGLES, 0, vertexCount);
            panel.classList.add('cinema-art--ready');
        }

        function tick(now) {
            frameRequest = 0;
            if (disposed || document.hidden || !desktop.matches || motion.matches) return;
            // 30 fps is sufficient for this deliberately slow movement.
            if (now - lastDraw >= 1000 / 30) {
                const delta = lastTime ? Math.min((now - lastTime) / 1000, .1) : 0;
                elapsed += delta;
                lastTime = now;
                lastDraw = now;
                const ease = 1 - Math.exp(-delta * 3);
                pointer.x += (pointer.targetX - pointer.x) * ease;
                pointer.y += (pointer.targetY - pointer.y) * ease;
                draw();
            }
            frameRequest = requestAnimationFrame(tick);
        }

        function syncPlayback() {
            cancelAnimationFrame(frameRequest);
            frameRequest = 0;
            lastTime = 0;
            lastDraw = 0;
            if (disposed || document.hidden || !desktop.matches) return;
            if (motion.matches) {
                elapsed = 0;
                pointer.x = pointer.y = 0;
                draw();
            } else {
                frameRequest = requestAnimationFrame(tick);
            }
        }

        function resize() {
            if (disposed) return;
            const bounds = canvas.getBoundingClientRect();
            width = bounds.width;
            height = bounds.height;
            // Cap the drawing surface as well as DPR for large / high-density monitors.
            const dpr = Math.min(window.devicePixelRatio || 1, 1.75, 1600 / Math.max(width, height, 1));
            canvas.width = Math.max(1, Math.round(width * dpr));
            canvas.height = Math.max(1, Math.round(height * dpr));
            draw();
            syncPlayback();
        }

        function movePointer(event) {
            if (motion.matches || event.pointerType === 'touch') return;
            const bounds = panel.getBoundingClientRect();
            pointer.targetX = (event.clientX - bounds.left) / bounds.width * 2 - 1;
            pointer.targetY = (event.clientY - bounds.top) / bounds.height * 2 - 1;
        }
        function resetPointer() {
            pointer.targetX = pointer.targetY = 0;
        }

        const observer = new ResizeObserver(resize);
        observer.observe(canvas);
        panel.addEventListener('pointermove', movePointer, { passive: true });
        panel.addEventListener('pointerleave', resetPointer);
        document.addEventListener('visibilitychange', syncPlayback);
        motion.addEventListener('change', syncPlayback);
        desktop.addEventListener('change', resize);

        function dispose() {
            if (disposed) return;
            disposed = true;
            cancelAnimationFrame(frameRequest);
            observer.disconnect();
            panel.removeEventListener('pointermove', movePointer);
            panel.removeEventListener('pointerleave', resetPointer);
            document.removeEventListener('visibilitychange', syncPlayback);
            motion.removeEventListener('change', syncPlayback);
            desktop.removeEventListener('change', resize);
            window.removeEventListener('pagehide', onPageHide);
            canvas.removeEventListener('webglcontextlost', onContextLost);
            release();
        }
        function onContextLost() {
            panel.classList.remove('cinema-art--ready');
            dispose();
        }
        function onPageHide(event) {
            // Keep a back/forward-cache page usable when the browser restores it.
            if (!event.persisted) dispose();
        }
        canvas.addEventListener('webglcontextlost', onContextLost);
        window.addEventListener('pagehide', onPageHide);
        resize();
    } catch {
        cancelAnimationFrame(frameRequest);
        panel.classList.remove('cinema-art--ready');
        release();
    }
}
