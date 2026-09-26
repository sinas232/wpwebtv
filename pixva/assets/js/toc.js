/**
 * رفتار فهرست مطالب: جمع‌شدن و هایلایت بخش جاری
 * خود فهرست سمت سرور ساخته می‌شود.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', () => {
		const toc = document.querySelector('.pixva-toc');
		if (!toc) {
			return;
		}
		const toggle = toc.querySelector('.pixva-toc__toggle');
		const list = toc.querySelector('.pixva-toc__list');
		if (toggle && list) {
			toggle.addEventListener('click', () => {
				const expanded = toggle.getAttribute('aria-expanded') === 'true';
				toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
				list.hidden = expanded;
				toc.classList.toggle('is-collapsed', expanded);
			});
		}

		// نوار پیشرفت مطالعه: در مرورگرهای بدون scroll-driven animations با rAF پر می‌شود.
		const bar = toc.querySelector('[data-toc-progress]');
		const supportsScrollTimeline = typeof CSS !== 'undefined' && CSS.supports && CSS.supports('animation-timeline', 'scroll()');
		if (bar && !supportsScrollTimeline) {
			let frame = 0;
			const update = () => {
				frame = 0;
				const doc = document.documentElement;
				const max = (doc.scrollHeight || 0) - (window.innerHeight || 0);
				const ratio = max > 0 ? Math.min(1, Math.max(0, (window.scrollY || doc.scrollTop || 0) / max)) : 0;
				bar.style.transform = 'scaleX(' + ratio.toFixed(4) + ')';
			};
			const onScroll = () => {
				if (!frame) {
					frame = window.requestAnimationFrame(update);
				}
			};
			window.addEventListener('scroll', onScroll, { passive: true });
			window.addEventListener('resize', onScroll, { passive: true });
			update();
		}

		const links = Array.from(toc.querySelectorAll('a[href^="#"]'));
		const map = new Map();
		links.forEach((link) => {
			const id = decodeURIComponent(link.getAttribute('href').slice(1));
			const heading = document.getElementById(id);
			if (heading) {
				map.set(heading, link);
			}
		});
		if (!map.size || !('IntersectionObserver' in window)) {
			return;
		}
		const observer = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) {
					return;
				}
				links.forEach((link) => link.classList.remove('is-active'));
				const active = map.get(entry.target);
				if (active) {
					active.classList.add('is-active');
				}
			});
		}, { rootMargin: '-30% 0px -55% 0px', threshold: 0.01 });
		map.forEach((link, heading) => observer.observe(heading));
	});
}());
