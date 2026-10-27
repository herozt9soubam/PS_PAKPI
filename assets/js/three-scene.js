import * as THREE from './three.module.min.js';

/**
 * P.S. Pakpi Enterprises — Editorial Abstract Background (Three.js / WebGL)
 * 
 * Inspired by subtle contemporary agency aesthetics:
 * - Fluid particle network & slow-moving orbital rings representing multi-sector synergy:
 *   "Construction → Supply → Services → Growth"
 * - Soft warm blueprint grid and delicate glowing nodal connections
 * - High performance, pointer parallax, scroll reactivity, and prefers-reduced-motion support.
 */

class EditorialHeroBackground {
    constructor(canvasContainerId = 'hero-webgl-bg') {
        this.container = document.getElementById(canvasContainerId);
        if (!this.container) return;

        this.reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        this.prefersReducedMotion = this.reducedMotionQuery.matches;

        this.mouse = { x: 0, y: 0, targetX: 0, targetY: 0 };
        this.scrollOffset = 0;
        this.isVisible = true;

        this.init();
    }

    init() {
        const width = this.container.clientWidth || window.innerWidth;
        const height = this.container.clientHeight || 720;

        // 1. Scene
        this.scene = new THREE.Scene();

        // 2. Camera
        this.camera = new THREE.PerspectiveCamera(42, width / height, 0.1, 100);
        this.camera.position.set(0, 1.8, 12);
        this.camera.lookAt(0, 0, 0);

        // 3. Renderer
        this.renderer = new THREE.WebGLRenderer({
            alpha: true,
            antialias: true,
            powerPreference: 'high-performance',
        });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
        this.renderer.domElement.className = 'hero-webgl-canvas';
        this.container.appendChild(this.renderer.domElement);

        // 4. Build Layers
        this.buildPerspectiveBase();
        this.buildOrbitalNodes();
        this.buildConnectedParticleField();

        // 5. Listeners
        this.setupListeners();

        // 6. Animation
        this.clock = new THREE.Clock();
        this.animate = this.animate.bind(this);
        this.animationId = requestAnimationFrame(this.animate);
    }

    /**
     * LAYER 1: Subtle architectural blueprint grid floor
     */
    buildPerspectiveBase() {
        this.gridGroup = new THREE.Group();

        // Grid lines with warm neutral & navy tint
        const size = 36;
        const divisions = 28;
        const gridHelper = new THREE.GridHelper(size, divisions, 0x1E3A8A, 0xDDD7CA);
        gridHelper.position.set(0, -2.4, -2);

        if (Array.isArray(gridHelper.material)) {
            gridHelper.material.forEach(m => {
                m.transparent = true;
                m.opacity = 0.18;
                m.depthWrite = false;
            });
        } else {
            gridHelper.material.transparent = true;
            gridHelper.material.opacity = 0.18;
            gridHelper.material.depthWrite = false;
        }

        this.gridGroup.add(gridHelper);
        this.scene.add(this.gridGroup);
    }

    /**
     * LAYER 2: Slow-moving orbital rings representing interconnected sectors
     */
    buildOrbitalNodes() {
        this.orbitalGroup = new THREE.Group();
        this.orbitalGroup.position.set(2.8, 0.6, -1);

        const ringMatNavy = new THREE.LineBasicMaterial({
            color: 0x1E3A8A,
            transparent: true,
            opacity: 0.28,
        });

        const ringMatGold = new THREE.LineBasicMaterial({
            color: 0xB08D3C,
            transparent: true,
            opacity: 0.35,
        });

        // Ring 1 - Inner orbit
        const ringGeo1 = new THREE.RingGeometry(2.2, 2.22, 64);
        const ring1 = new THREE.LineLoop(ringGeo1, ringMatNavy);
        ring1.rotation.x = Math.PI / 3;
        this.orbitalGroup.add(ring1);
        this.ring1 = ring1;

        // Ring 2 - Outer inclined orbit
        const ringGeo2 = new THREE.RingGeometry(3.6, 3.62, 64);
        const ring2 = new THREE.LineLoop(ringGeo2, ringMatGold);
        ring2.rotation.x = Math.PI / 4;
        ring2.rotation.y = Math.PI / 6;
        this.orbitalGroup.add(ring2);
        this.ring2 = ring2;

        // Central geometric node: Octahedron (Pillar of Quality)
        const nodeGeo = new THREE.OctahedronGeometry(0.7, 0);
        const nodeMat = new THREE.LineSegments(
            new THREE.EdgesGeometry(nodeGeo),
            new THREE.LineBasicMaterial({ color: 0xB08D3C, transparent: true, opacity: 0.45 })
        );
        this.orbitalGroup.add(nodeMat);
        this.centralNode = nodeMat;

        this.scene.add(this.orbitalGroup);
    }

    /**
     * LAYER 3: Connected particle constellation (Construction -> Supply -> Services -> Growth)
     */
    buildConnectedParticleField() {
        const isMobile = window.innerWidth < 768;
        this.nodeCount = isMobile ? 32 : 54;
        this.particlePositions = [];
        this.particleVelocities = [];

        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(this.nodeCount * 3);
        const colors = new Float32Array(this.nodeCount * 3);

        const navy = new THREE.Color(0x1E3A8A);
        const gold = new THREE.Color(0xB08D3C);
        const blue = new THREE.Color(0x2563EB);

        for (let i = 0; i < this.nodeCount; i++) {
            const x = (Math.random() - 0.5) * 20;
            const y = (Math.random() - 0.5) * 8 + 0.5;
            const z = (Math.random() - 0.5) * 10 - 2;

            positions[i * 3] = x;
            positions[i * 3 + 1] = y;
            positions[i * 3 + 2] = z;

            this.particlePositions.push(new THREE.Vector3(x, y, z));
            this.particleVelocities.push(new THREE.Vector3(
                (Math.random() - 0.5) * 0.003,
                (Math.random() - 0.5) * 0.003,
                (Math.random() - 0.5) * 0.002
            ));

            const rand = Math.random();
            const col = rand > 0.6 ? gold : (rand > 0.3 ? navy : blue);
            colors[i * 3] = col.r;
            colors[i * 3 + 1] = col.g;
            colors[i * 3 + 2] = col.b;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        const particleMat = new THREE.PointsMaterial({
            size: 0.09,
            vertexColors: true,
            transparent: true,
            opacity: 0.6,
            depthWrite: false,
        });

        this.pointCloud = new THREE.Points(geometry, particleMat);
        this.scene.add(this.pointCloud);

        // Constellation Connection Lines
        const maxLines = this.nodeCount * 3;
        this.linesGeometry = new THREE.BufferGeometry();
        this.linePositions = new Float32Array(maxLines * 6);
        this.linesGeometry.setAttribute('position', new THREE.BufferAttribute(this.linePositions, 3));

        this.linesMaterial = new THREE.LineSegments(
            this.linesGeometry,
            new THREE.LineBasicMaterial({
                color: 0x1E3A8A,
                transparent: true,
                opacity: 0.14,
                depthWrite: false,
            })
        );
        this.scene.add(this.linesMaterial);
    }

    setupListeners() {
        if (window.matchMedia('(min-width: 768px)').matches) {
            window.addEventListener('mousemove', (e) => {
                this.mouse.targetX = (e.clientX / window.innerWidth - 0.5) * 0.45;
                this.mouse.targetY = (e.clientY / window.innerHeight - 0.5) * 0.35;
            }, { passive: true });
        }

        window.addEventListener('scroll', () => {
            this.scrollOffset = window.scrollY || 0;
        }, { passive: true });

        this.onResize = () => {
            if (!this.container) return;
            const w = this.container.clientWidth || window.innerWidth;
            const h = this.container.clientHeight || 720;
            this.camera.aspect = w / h;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(w, h);
            this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
        };
        window.addEventListener('resize', this.onResize);

        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    this.isVisible = entry.isIntersecting;
                });
            }, { threshold: 0.05 });
            this.observer.observe(this.container);
        }

        this.reducedMotionQuery.addEventListener('change', (e) => {
            this.prefersReducedMotion = e.matches;
        });
    }

    animate() {
        this.animationId = requestAnimationFrame(this.animate);

        if (!this.isVisible) return;

        const time = this.clock.getElapsedTime();

        if (!this.prefersReducedMotion) {
            this.mouse.x += (this.mouse.targetX - this.mouse.x) * 0.05;
            this.mouse.y += (this.mouse.targetY - this.mouse.y) * 0.05;

            this.camera.position.x = this.mouse.x * 0.7;
            this.camera.position.y = 1.8 - (this.mouse.y * 0.4) - (this.scrollOffset * 0.001);
            this.camera.lookAt(0, 0, 0);

            // Orbit Rotation
            if (this.orbitalGroup) {
                this.orbitalGroup.rotation.y = time * 0.05;
                if (this.ring1) this.ring1.rotation.z = time * 0.08;
                if (this.ring2) this.ring2.rotation.z = -time * 0.06;
                if (this.centralNode) {
                    this.centralNode.rotation.y = time * 0.15;
                    this.centralNode.rotation.x = time * 0.1;
                }
            }

            // Update particle cloud and connecting lines
            if (this.pointCloud && this.particlePositions) {
                const posArr = this.pointCloud.geometry.attributes.position.array;
                let lineIdx = 0;
                const connectionDist = 2.6;

                for (let i = 0; i < this.nodeCount; i++) {
                    const p = this.particlePositions[i];
                    const v = this.particleVelocities[i];

                    p.add(v);
                    if (p.x < -10 || p.x > 10) v.x *= -1;
                    if (p.y < -3.5 || p.y > 4.5) v.y *= -1;
                    if (p.z < -6 || p.z > 4) v.z *= -1;

                    posArr[i * 3] = p.x;
                    posArr[i * 3 + 1] = p.y + Math.sin(time * 0.5 + i) * 0.003;
                    posArr[i * 3 + 2] = p.z;

                    // Compute connections with nearby nodes
                    for (let j = i + 1; j < this.nodeCount; j++) {
                        const p2 = this.particlePositions[j];
                        const dist = p.distanceTo(p2);
                        if (dist < connectionDist && lineIdx < this.linePositions.length - 6) {
                            this.linePositions[lineIdx++] = p.x;
                            this.linePositions[lineIdx++] = p.y;
                            this.linePositions[lineIdx++] = p.z;
                            this.linePositions[lineIdx++] = p2.x;
                            this.linePositions[lineIdx++] = p2.y;
                            this.linePositions[lineIdx++] = p2.z;
                        }
                    }
                }

                // Clear unused line vertices
                for (let k = lineIdx; k < this.linePositions.length; k++) {
                    this.linePositions[k] = 0;
                }

                this.pointCloud.geometry.attributes.position.needsUpdate = true;
                this.linesGeometry.attributes.position.needsUpdate = true;
            }
        }

        this.renderer.render(this.scene, this.camera);
    }

    destroy() {
        if (this.animationId) cancelAnimationFrame(this.animationId);
        if (this.observer) this.observer.disconnect();
        window.removeEventListener('resize', this.onResize);
        if (this.renderer && this.renderer.domElement && this.renderer.domElement.parentNode) {
            this.renderer.domElement.parentNode.removeChild(this.renderer.domElement);
        }
        if (this.renderer) this.renderer.dispose();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const bgContainer = document.getElementById('hero-webgl-bg') || document.getElementById('service-webgl-bg');
    if (bgContainer) {
        new EditorialHeroBackground(bgContainer.id);
    }
});
