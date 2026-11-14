/**
 * 02 — Material Reveal.
 * The image unmasks bottom → top, then one soft light sheen sweeps across the metal
 * (the sheen is .material::after, moved by the --sheen custom property).
 * On desktop the sheen replays on hover.
 *
 * Elements inside a [data-motion="stagger"] list are revealed by staggered-entrance.js,
 * in step with their card; this module handles standalone images and the hover sheen.
 */
export function sheen(el, ctx, delay = 0) {
	const from = ctx.rtl ? '140%' : '-140%';
	const to = ctx.rtl ? '-140%' : '140%';
	return gsap.fromTo(el, { '--sheen': from }, { '--sheen': to, duration: 1.1, ease: 'power1.inOut', delay });
}

export function reveal(el, ctx, delay = 0) {
	gsap.fromTo(el, { clipPath: 'inset(100% 0 0 0)' },
		{ clipPath: 'inset(0% 0 0 0)', duration: 0.9, ease: 'expo.out', delay });
	if (!ctx.small) sheen(el, ctx, delay + 0.5);
}

export function bindHover(el, ctx) {
	if (!ctx.finePointer || el.dataset.sheenHover) return;
	el.dataset.sheenHover = '1';
	const target = el.closest('.card, .cat-tile, .post-card') || el;
	target.addEventListener('pointerenter', () => {
		if (!gsap.isTweening(el)) sheen(el, ctx);
	});
}

export function init(els, ctx) {
	els.forEach((el) => {
		bindHover(el, ctx);
		if (el.closest('[data-motion="stagger"]') || el.dataset.motionDone) return;
		el.dataset.motionDone = '1';
		ScrollTrigger.create({
			trigger: el,
			start: 'top 88%',
			once: true,
			onEnter: () => reveal(el, ctx)
		});
	});
}
