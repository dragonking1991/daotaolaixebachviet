<div class="photoUpload-zone">
	<div class="photoUpload-detail" id="photoUpload-preview"><img class="rounded" src="<?=@$photoDetailicon?>" onerror="src='assets/images/noimage.png'" alt="Alt Photo"/></div>
	<label class="photoUpload-file" id="photo-zone_icon" for="file-zone_icon">
		<input type="file" name="icon" id="file-zone_icon">
		<i class="fas fa-cloud-upload-alt"></i>
		<p class="photoUpload-drop">Kéo và thả hình vào đây</p>
		<p class="photoUpload-or">hoặc</p>
		<p class="photoUpload-choose btn btn-sm bg-gradient-success">Chọn hình</p>
	</label>
	<div class="photoUpload-dimension"><?=@$dimensionicon?></div>
</div>