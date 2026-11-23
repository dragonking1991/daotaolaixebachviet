<?php
/* Link "Lý thuyết online": đọc từ table_dt_setting key 'lythuyet_online_url' (đổi trong admin Đào tạo → Lý thuyết), mặc định là link LMS cũ. */
if(!function_exists('dt_online_theory_default_url'))
{
	function dt_online_theory_default_url()
	{
		return 'https://bachviet.huelms.com/user/login?b=1TZKKGM95F1EkgDPrRW7UanN7/sM+xzIjvCvr6Sh8QJou7RZN+ynEx8BkhIorsMG';
	}

	function dt_online_theory_url()
	{
		global $d;
		$row = null;
		try { $row = $d->rawQueryOne("select sval from #_dt_setting where skey = 'lythuyet_online_url' limit 0,1"); }
		catch(Exception $e) { $row = null; }
		$url = ($row && isset($row['sval'])) ? trim($row['sval']) : '';
		return preg_match('#^https?://#i', $url) ? $url : dt_online_theory_default_url();
	}
}
