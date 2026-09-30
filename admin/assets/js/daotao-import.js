/* Đào tạo — dropzone import: kéo-thả + chọn nhiều file .xlsx/.xls, upload tuần tự.
   1 file: submit thường (giữ nguyên luồng server + "Xem trước").
   Nhiều file: gửi lần lượt (dt_ajax=1), gộp kết quả rồi cho về danh sách. */
(function(){
	function init(){
		var zones = document.querySelectorAll('.dt-dropzone');
		if(!zones.length) return;
		for(var i = 0; i < zones.length; i++) setupZone(zones[i]);
	}

	function escapeHtml(s){
		return String(s).replace(/[&<>"']/g, function(c){
			return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
		});
	}
	function humanSize(n){
		if(!n && n !== 0) return '';
		if(n < 1024) return n + ' B';
		if(n < 1048576) return (n/1024).toFixed(1) + ' KB';
		return (n/1048576).toFixed(1) + ' MB';
	}
	function isExcel(f){ return /\.(xlsx|xls)$/i.test(f.name); }

	function setupZone(zone){
		var input = zone.querySelector('.dt-dz-input');
		var list = zone.querySelector('.dt-dz-list');
		var form = zone.closest ? zone.closest('form') : null;
		if(!input || !list) return;

		var monOpts = null;
		try { var rawMon = zone.getAttribute('data-mon'); if(rawMon) monOpts = JSON.parse(rawMon); } catch(e){ monOpts = null; }
		var monSel = {}; // key file -> môn đã chọn

		function fileKey(f){ return f.name + '|' + f.size; }
		function normName(s){
			var n = String(s).toLowerCase();
			if(n.normalize) n = n.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
			return n.replace(/đ/g, 'd').replace(/[^a-z0-9]/g, '');
		}
		function detectMon(name){
			if(!monOpts) return '';
			var n = normName(name);
			for(var i = 0; i < monOpts.length; i++){
				var kws = monOpts[i].kw || [];
				for(var j = 0; j < kws.length; j++){ if(kws[j] && n.indexOf(kws[j]) !== -1) return monOpts[i].v; }
			}
			return '';
		}

		function currentFiles(){ return Array.prototype.slice.call(input.files || []); }

		function setFiles(arr){
			try {
				var dt = new DataTransfer();
				arr.forEach(function(f){ dt.items.add(f); });
				input.files = dt.files;
			} catch(e){ /* DataTransfer không hỗ trợ: giữ nguyên input.files */ }
			render();
		}

		function render(){
			var files = currentFiles();
			list.innerHTML = '';
			files.forEach(function(f, idx){
				var li = document.createElement('li');
				li.className = 'dt-dz-item';
				li.innerHTML = '<i class="fas fa-file-excel"></i><span class="dt-dz-name">' + escapeHtml(f.name) + '</span>';

				if(monOpts){
					var key = fileKey(f);
					var cur = (monSel[key] !== undefined) ? monSel[key] : detectMon(f.name);
					monSel[key] = cur;
					var sel = document.createElement('select');
					sel.className = 'dt-dz-mon form-control form-control-sm';
					sel.title = 'Môn học của file này';
					var ph = document.createElement('option'); ph.value = ''; ph.textContent = '— Chọn môn —'; sel.appendChild(ph);
					monOpts.forEach(function(o){ var op = document.createElement('option'); op.value = o.v; op.textContent = o.l; if(o.v === cur) op.selected = true; sel.appendChild(op); });
					if(!cur) sel.classList.add('is-empty');
					sel.addEventListener('change', function(){ monSel[key] = this.value; this.classList.toggle('is-empty', !this.value); });
					sel.addEventListener('click', function(ev){ ev.stopPropagation(); });
					li.appendChild(sel);
				}

				var spacer = document.createElement('span'); spacer.className = 'dt-dz-spacer';
				li.appendChild(spacer);
				var size = document.createElement('span'); size.className = 'dt-dz-size'; size.textContent = humanSize(f.size);
				li.appendChild(size);

				var rm = document.createElement('button');
				rm.type = 'button'; rm.className = 'dt-dz-remove'; rm.setAttribute('aria-label', 'Bỏ file'); rm.innerHTML = '&times;';
				rm.addEventListener('click', function(ev){
					ev.preventDefault(); ev.stopPropagation();
					var next = currentFiles(); next.splice(idx, 1); setFiles(next);
				});
				li.appendChild(rm);
				list.appendChild(li);
			});
			zone.classList.toggle('has-files', files.length > 0);
		}

		input.addEventListener('change', render);

		['dragenter','dragover'].forEach(function(ev){
			zone.addEventListener(ev, function(e){ e.preventDefault(); e.stopPropagation(); zone.classList.add('is-drag'); });
		});
		['dragleave','dragend','drop'].forEach(function(ev){
			zone.addEventListener(ev, function(e){ e.preventDefault(); e.stopPropagation(); zone.classList.remove('is-drag'); });
		});
		zone.addEventListener('drop', function(e){
			var dropped = Array.prototype.slice.call(e.dataTransfer.files || []).filter(isExcel);
			if(!dropped.length) return;
			setFiles(currentFiles().concat(dropped));
		});

		if(!form) return;
		form.addEventListener('submit', function(e){
			var files = currentFiles();
			var submitter = e.submitter || document.activeElement;
			var isPreview = submitter && submitter.name === 'preview';

			if(files.length === 0){
				e.preventDefault();
				notify(false, 'Vui lòng chọn ít nhất 1 file Excel (.xlsx/.xls).');
				return;
			}

			// Lý thuyết: mỗi file một môn (tự nhận theo tên, có thể chỉnh) → luôn gửi tuần tự kèm môn riêng.
			if(monOpts){
				var mons = files.map(function(f){ return monSel[fileKey(f)] || detectMon(f.name); });
				var missing = [];
				files.forEach(function(f, i){ if(!mons[i]) missing.push(f.name); });
				if(missing.length){ e.preventDefault(); notify(false, 'Chưa chọn môn học cho: ' + missing.join(', ')); return; }
				e.preventDefault();
				uploadSequential(form, zone, files, mons);
				return;
			}

			if(isPreview || files.length === 1) return; // submit thường

			e.preventDefault();
			uploadSequential(form, zone, files);
		});
	}

	function notify(ok, msg){
		if(typeof Swal !== 'undefined'){
			Swal.fire({ icon: ok ? 'success' : 'warning', title: ok ? 'Hoàn tất' : 'Chú ý', text: msg });
		} else { alert(msg); }
	}

	function uploadSequential(form, zone, files, monValues){
		var endpoint = form.getAttribute('action') || window.location.href;
		var back = zone.getAttribute('data-back') || '';

		var overlay = document.createElement('div');
		overlay.className = 'dt-dz-overlay';
		var panel = document.createElement('div');
		panel.className = 'dt-dz-panel';
		panel.innerHTML = '<button type="button" class="dt-dz-close" aria-label="Đóng">&times;</button><div class="dt-dz-panel-head"><i class="fas fa-cloud-upload-alt"></i> Đang import ' + files.length + ' file...</div>';
		panel.querySelector('.dt-dz-close').addEventListener('click', function(){
			if(overlay.parentNode) overlay.parentNode.removeChild(overlay);
		});
		var ul = document.createElement('ul');
		ul.className = 'dt-dz-progress';
		files.forEach(function(f, i){
			var li = document.createElement('li');
			li.id = 'dt-dz-p-' + i;
			li.innerHTML = '<span class="dt-dz-p-ic"><i class="far fa-clock"></i></span><span class="dt-dz-p-name">' + escapeHtml(f.name) + '</span><span class="dt-dz-p-msg"></span>';
			ul.appendChild(li);
		});
		panel.appendChild(ul);
		var foot = document.createElement('div');
		foot.className = 'dt-dz-panel-foot';
		panel.appendChild(foot);
		overlay.appendChild(panel);
		document.body.appendChild(overlay);

		function setRow(i, state, msg){
			var li = document.getElementById('dt-dz-p-' + i);
			if(!li) return;
			li.className = 'is-' + state;
			var ic = li.querySelector('.dt-dz-p-ic');
			var mo = li.querySelector('.dt-dz-p-msg');
			if(ic) ic.innerHTML = state === 'running' ? '<i class="fas fa-spinner fa-spin"></i>' : (state === 'ok' ? '<i class="fas fa-check-circle"></i>' : (state === 'warn' ? '<i class="fas fa-exclamation-triangle"></i>' : '<i class="fas fa-times-circle"></i>'));
			if(mo && msg) mo.textContent = msg;
		}

		var okCount = 0, failCount = 0;
		var chain = Promise.resolve();
		files.forEach(function(f, i){
			chain = chain.then(function(){
				setRow(i, 'running');
				var fd = new FormData(form);
				fd.delete('file-excel');
				fd.append('file-excel', f);
				fd.append('dt_ajax', '1');
				if(monValues) fd.set('mon', monValues[i]);
				return fetch(endpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
					.then(function(r){ return r.json().catch(function(){ return { success: false, message: 'Phản hồi không hợp lệ từ máy chủ.' }; }); })
					.then(function(j){
						if(j && j.success){ okCount++; var warn = /lỗi/i.test(j.message || ''); setRow(i, warn ? 'warn' : 'ok', j.message || 'Thành công'); }
						else { failCount++; setRow(i, 'fail', (j && j.message) || 'Lỗi'); }
					})
					.catch(function(){ failCount++; setRow(i, 'fail', 'Lỗi kết nối'); });
			});
		});

		chain.then(function(){
			panel.querySelector('.dt-dz-panel-head').innerHTML = '<i class="fas fa-flag-checkered"></i> Hoàn tất: ' + okCount + ' thành công, ' + failCount + ' lỗi';
			var btn = document.createElement('a');
			btn.className = 'btn btn-sm bg-gradient-primary text-white';
			btn.href = back || '#';
			btn.innerHTML = '<i class="fas fa-list mr-1"></i>Xem danh sách';
			foot.appendChild(btn);
			var close = document.createElement('button');
			close.type = 'button';
			close.className = 'btn btn-sm bg-gradient-secondary text-white ml-2';
			close.textContent = 'Đóng';
			close.addEventListener('click', function(){ if(back) window.location.href = back; else overlay.remove(); });
			foot.appendChild(close);
		});
	}

	if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();
