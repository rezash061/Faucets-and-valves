/**
 * 03 — Staggered Entrance.
 * Children of [data-motion="stagger"] enter in batches of up to 8: opacity 0 → 1, y 24 → 0,
 * 0.08 s apart. DOM order already follows the RTL visual order, so rows fill right to left.
 * A card's [data-motion="material"] image reveals in step with the card.
 *
 * Safe to call again after AJAX updates: only children not yet shown are handled.
 */
import { reveal, bindHover } from './material-reveal.js';

export function init(lists, ctx) {
	const step = ctx.small ? 0.05 : 0.08;

	lists.forEach((list) => {
		const items = Array.from(list.children).filter((el) => !el.dataset.motionDone);
		if (!items.length) return;
		items.forEach((el) => { el.dataset.motionDone = '1'; });

		ScrollTrigger.batch(items, {
			start: 'top 90%',
			once: true,
			batchMax: 8,
			onEnter: (batch) => {
				gsap.fromTo(batch, { opacity: 0, y: ctx.small ? 16 : 24 },
					{ opacity: 1, y: 0, duration: 0.6, stagger: step, ease: 'power2.out', clearProps: 'transform' }); // Keep inline opacity: CSS start state hides until shown.
				batch.forEach((item, i) => {
					item.classList.add('is-in');
					const m = item.querySelector('[data-motion="material"]');
					if (m) {
						bindHover(m, ctx);
						reveal(m, ctx, i * step);
					}
				});
			}
		});
	});
}
