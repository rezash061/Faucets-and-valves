/**
 * 01 — Cinematic Reveal.
 * Mask wipe in the reading direction, slow zoom-out, headline words rise in.
 *
 * Markup: [data-motion="cinematic"] > .hero__mask > .hero__img
 *         .hero__eyebrow, .hero__title .w > span, .hero__sub, .hero__ctas
 */
export function init(heroes, ctx) {
	heroes.forEach((hero) => {
		if (hero.dataset.motionDone) return;
		hero.dataset.motionDone = '1';

		const mask = hero.querySelector('.hero__mask');
		const img = hero.querySelector('.hero__img');
		const words = hero.querySelectorAll('.hero__title .w > span');
		const after = hero.querySelectorAll('.hero__sub, .hero__ctas');
		const eyebrow = hero.querySelector('.hero__eyebrow');

		const play = () => {
			const tl = gsap.timeline({ defaults: { ease: 'expo.out' } });
			if (mask) {
				tl.fromTo(mask,
					{ clipPath: ctx.rtl ? 'inset(0 0 0 92%)' : 'inset(0 92% 0 0)' },
					{ clipPath: 'inset(0 0 0 0%)', duration: ctx.small ? 1.0 : 1.4 }, 0);
			}
			if (img) {
				tl.fromTo(img, { scale: ctx.small ? 1.06 : 1.12 }, { scale: 1, duration: 2.2, ease: 'power2.out' }, 0);
			}
			if (eyebrow) {
				tl.fromTo(eyebrow, { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.7, ease: 'power2.out' }, 0.45);
			}
			if (words.length) {
				tl.fromTo(words, { opacity: 0, yPercent: 60 },
					{ opacity: 1, yPercent: 0, duration: 0.9, stagger: ctx.small ? 0.05 : 0.07, ease: 'power3.out' }, 0.6);
			}
			if (after.length) {
				tl.fromTo(after, { opacity: 0, y: 12 },
					{ opacity: 1, y: 0, duration: 0.7, stagger: 0.1, ease: 'power2.out', clearProps: 'transform' }, '-=0.45');
			}
		};

		// Start once the hero image is decoded so the wipe never reveals an empty frame.
		const ready = img && img.tagName === 'IMG' && img.decode ? img.decode().catch(() => {}) : Promise.resolve();
		Promise.race([ready, new Promise((r) => setTimeout(r, 1200))]).then(play);
	});
}
