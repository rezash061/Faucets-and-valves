// The theme already loads GSAP + ScrollTrigger as separate scripts (window.gsap);
// this shim lets the scene modules use them without bundling a second copy.
export const gsap = window.gsap;
export const ScrollTrigger = window.ScrollTrigger;
