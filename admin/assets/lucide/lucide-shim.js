/* Lucide shim: tự động thay icon FontAwesome (<i class="fa...">) bằng icon Lucide
   trên toàn bộ admin, không cần sửa template. Giữ lại mũi tên treeview của
   AdminLTE (fa-angle-left) để không vỡ hiệu ứng đóng/mở menu. */
(function(){
	var MAP = {
		'address-book':'contact','arrow-left':'arrow-left','balance-scale-right':'scale','bars':'menu',
		'bell':'bell','birthday-cake':'cake','book':'book','bookmark':'bookmark','boxes':'boxes',
		'briefcase':'briefcase','building':'building-2','calendar-alt':'calendar','camera':'camera','car':'car',
		'caret-square-right':'square-chevron-right','chalkboard-teacher':'presentation','check':'check',
		'check-circle':'circle-check','check-double':'check-check','check-square':'square-check',
		'clipboard-check':'clipboard-check','clipboard-list':'clipboard-list','clone':'copy',
		'cloud-upload-alt':'cloud-upload','cogs':'settings','comment-dots':'message-circle','divide':'divide',
		'download':'download','edit':'square-pen','envelope':'mail','envelope-open':'mail-open',
		'exclamation-triangle':'triangle-alert','eye':'eye','eye-slash':'eye-off','file-excel':'file-spreadsheet',
		'file-export':'file-output','file-import':'file-input','file-invoice':'file-text',
		'file-invoice-dollar':'receipt','file-upload':'file-up','file-word':'file-text','filter':'filter',
		'gas-pump':'fuel','graduation-cap':'graduation-cap','hand-holding-usd':'hand-coins','hashtag':'hash',
		'hourglass-half':'hourglass','id-badge':'contact','id-card':'id-card','info':'info','info-circle':'info',
		'key':'key','language':'languages','layer-group':'layers','lightbulb':'lightbulb','list':'list',
		'lock':'lock','magic':'wand-sparkles','mail-bulk':'mails','map-marker-alt':'map-pin','minus':'minus',
		'newspaper':'newspaper','paper-plane':'send','phone':'phone','photo-video':'images','plus':'plus',
		'random':'shuffle','receipt':'receipt','redo':'redo-2','reply':'reply','save':'save','search':'search',
		'share-alt':'share-2','shopping-bag':'shopping-bag','shopping-cart':'shopping-cart','shuttle-van':'bus',
		'sign-out-alt':'log-out','spinner':'loader-circle','square':'square','stamp':'stamp',
		'tachometer-alt':'gauge','tags':'tags','thumbs-up':'thumbs-up','times':'x','times-circle':'circle-x',
		'trash':'trash-2','trash-alt':'trash-2','undo':'undo-2','upload':'upload','user':'user',
		'user-cog':'user-cog','user-graduate':'graduation-cap','user-times':'user-x','users':'users'
	};
	// Giữ nguyên FontAwesome cho các icon AdminLTE điều khiển bằng CSS/animation
	var EXCLUDE = { 'angle-left':1, 'angle-right':1, 'angle-down':1, 'angle-up':1 };
	var SIZE = /^(fw|lg|sm|xs|2x|3x|4x|5x|6x|7x|8x|9x|10x|pull-left|pull-right|border|rotate-90|rotate-180|rotate-270|flip-horizontal|flip-vertical)$/;

	function iconName(cls){
		var m = cls.match(/\bfa-([a-z0-9-]+)/g);
		if(!m) return null;
		for(var i=0;i<m.length;i++){
			var n = m[i].slice(3);
			if(n === 'spin' || n === 'pulse' || SIZE.test(n)) continue;
			return n;
		}
		return null;
	}

	function convert(root){
		var list = (root || document).querySelectorAll('i[class*="fa-"]');
		var changed = false;
		for(var i=0; i<list.length; i++){
			var el = list[i];
			if(el.getAttribute('data-lucide')) continue;
			var cls = el.getAttribute('class') || '';
			var name = iconName(cls);
			if(!name || EXCLUDE[name]) continue;
			var lu = MAP[name];
			if(!lu) continue; // không có ánh xạ → giữ FontAwesome
			var keep = [];
			cls.split(/\s+/).forEach(function(c){ if(c && c.indexOf('fa') !== 0) keep.push(c); });
			keep.push('lucide-fa');
			if(/\bfa-spin\b|\bfa-pulse\b/.test(cls)) keep.push('lucide-spin');
			el.setAttribute('class', keep.join(' '));
			el.setAttribute('data-lucide', lu);
			el.textContent = '';
			changed = true;
		}
		if(changed && window.lucide && lucide.createIcons) lucide.createIcons();
	}

	var obs = null, timer = null;
	function scheduled(){
		if(timer) return;
		timer = setTimeout(function(){
			timer = null;
			if(obs) obs.disconnect();
			convert(document);
			if(obs) obs.observe(document.body, { childList:true, subtree:true });
		}, 100);
	}

	function boot(){
		if(!window.lucide){ return; }
		convert(document);
		if(window.MutationObserver){
			obs = new MutationObserver(function(muts){
				for(var i=0;i<muts.length;i++){ if(muts[i].addedNodes && muts[i].addedNodes.length){ scheduled(); return; } }
			});
			obs.observe(document.body, { childList:true, subtree:true });
		}
	}

	if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
	else boot();
})();
