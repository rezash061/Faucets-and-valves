/**
 * Count-up for stat numbers ([data-motion="countup"][data-to]).
 * The final number is already in the HTML; this only animates from 0 when it scrolls into view.
 * Numbers are formatted in Persian (fa-IR).
 */
export function init(els, ctx) {
	const fmt = new Intl.NumberFormat('fa-IR');
	els.forEach((el) => {
		if (el.dataset.motionDone) return;
		el.dataset.motionDone = '1';
		const to = parseFloat(el.dataset.to);
		if (!isFinite(to)) return;
		const state = { v: 0 };
		ScrollTrigger.create({
			trigger: el,
			start: 'top 90%',
			once: true,
			onEnter: () => {
				gsap.to(state, {
					v: to,
					duration: ctx.small ? 1.4 : 2,
					ease: 'power3.out',
					onUpdate: () => { el.textContent = fmt.format(Math.round(state.v)); }
				});
			}
		});
	});
}
