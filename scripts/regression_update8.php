<?php
if(PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if(($argv[1] ?? '') !== '--local-db') exit("Usage: php scripts/regression_update8.php --local-db [samples-directory] [--payments-only]\n");
define('LIBRARIES', dirname(__DIR__).'/libraries/');
define('SOURCES', dirname(__DIR__).'/admin/sources/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
require SOURCES.'daotao/config_daotao.php';
require SOURCES.'daotao/helpers.php';
require SOURCES.'daotao/schema.php';
require SOURCES.'daotao/excel_open.php';
require SOURCES.'daotao/export.php';
require SOURCES.'daotao/khoa.php';
require SOURCES.'daotao/hocvien.php';
require SOURCES.'daotao/dat.php';
require SOURCES.'daotao/taichinh.php';
require SOURCES.'daotao/sathach.php';
require SOURCES.'xangdau/daotao_sync.php';

function dt_backup_tables($tables, $tag) { return 'test-no-backup'; }
function dt_audit($action, $entity, $affected, $detail) {}
class Update8Notice extends RuntimeException
{
    public $success;
    public function __construct($message, $success) { parent::__construct($message); $this->success=$success; }
}
class Update8Functions
{
    public function transfer($message, $url, $success=true) { throw new Update8Notice($message,$success); }
    public function redirect($url) { throw new Update8Notice($url,true); }
}
function dt_import_notice($message,$url,$success=true) { throw new Update8Notice($message,$success); }
function u8_check($ok,$message) { if(!$ok) throw new RuntimeException($message); }
function u8_query($sql,$params=array())
{
    global $d;
    $result=$d->rawQuery($sql,$params);
    if($d->getLastErrorCode()!=='00000') throw new RuntimeException('SQL failure: '.json_encode($d->getLastError()));
    return $result;
}
function u8_action($callback,$expected=true)
{
    try { $callback(); throw new RuntimeException('Missing action outcome'); }
    catch(Update8Notice $notice) { u8_check($notice->success===$expected,$notice->getMessage()); }
}
set_exception_handler(function($error) { fwrite(STDERR, $error->getMessage()."\n".$error->getTraceAsString()."\n"); exit(1); });
$func=new Update8Functions();
$login_admin='update8_test';
$tag='U8-REG-'.bin2hex(random_bytes(4));
$_SESSION=array($login_admin=>array('username'=>$tag),'dt_finance_csrf'=>'test','xd_dat_csrf'=>'test');
$courseIds=array(); $studentIds=array(); $periodIds=array();
$samples=$argv[2] ?? '/tmp/update8';
dt_ensure_tables();
dt_ensure_update8_schema();
try
{
    foreach(array('B1','CE','A1') as $hang)
    {
        u8_query('insert into #_dt_khoa (ma_khoa,ten_khoa,hang,he_daotao) values (?,?,?,?)',array($tag.'-'.$hang,$tag,$hang,$hang==='CE'?'DAT':'CK'));
        $courseIds[$hang]=(int)$d->getLastInsertId();
        for($index=1;$index<=2;$index++)
        {
            $cccd=substr(hash('sha256',$tag.$hang.$index),0,11);
            $cccd='99'.substr(preg_replace('/\D/','', $cccd).'1234567890',0,10);
            u8_query('insert into #_dt_hocvien (id_khoa,ma_hv,cccd,hoten,hang,hoc_phi_da_dong,hoc_phi_con_thieu) values (?,?,?,?,?,?,?)',array($courseIds[$hang],$tag.'-'.$index,$cccd,$tag.' '.$hang.' '.$index,$hang,12000000,3000000));
            $studentIds[$hang][$index]=(int)$d->getLastInsertId();
        }
    }
    $bc=$courseIds['B1']; $ids=$studentIds['B1'];
    $_POST=array('id_khoa'=>$bc,'group'=>'bc','csrf'=>'test','loai'=>'tam_ung','ngay'=>'2026-10-24','request_key'=>bin2hex(random_bytes(16)),'ids'=>array_values($ids),'so_tien_tam_ung'=>'500000','tien_luong'=>'1','tien_nhien_lieu'=>'2');
    u8_action('dt_finance_save',false);
    u8_check(count(u8_query('select id from #_dt_taichinh where id_hocvien in (?,?)',array_values($ids)))===0,'B students without DAT must not receive transactions');
    $firstStudent=$d->rawQueryOne('select * from #_dt_hocvien where id=?',array($ids[1]));
    foreach(array(array(1,40,1,0),array(19,800,0,2)) as $index=>$session)
        u8_query('insert into #_dt_dat_phien (id_khoa,cccd,ma_hv,ma_phien,gio_thuchanh,km,la_xe_tudong,gio_dem) values (?,?,?,?,?,?,?,?)',array($bc,$firstStudent['cccd'],$firstStudent['ma_hv'],$tag.'-DAT-'.$index,$session[0],$session[1],$session[2],$session[3]));
    $secondStudent=$d->rawQueryOne('select * from #_dt_hocvien where id=?',array($ids[2]));
    u8_query('insert into #_dt_dat_override (id_khoa,cccd,dat) values (?,?,1)',array($bc,$secondStudent['cccd']));
    foreach(array('', '0', '-1', 'abc') as $invalidAdvance)
    {
        $_POST['so_tien_tam_ung']=$invalidAdvance;
        u8_action('dt_finance_save',false);
    }
    $_POST['so_tien_tam_ung']='500000';
    u8_action('dt_finance_save');
    $advances=u8_query('select so_tien,tien_luong,tien_nhien_lieu from #_dt_taichinh where id_hocvien in (?,?)',array_values($ids));
    u8_check(count($advances)===2 && (int)$advances[0]['so_tien']===500000 && (int)$advances[0]['tien_luong']===0 && (int)$advances[0]['tien_nhien_lieu']===0,'Advance must use the manually entered amount, not salary plus fuel');
    u8_action('dt_finance_save',false);
    u8_check(count(u8_query('select id from #_dt_taichinh where id_hocvien in (?,?)',array_values($ids)))===2,'Repeated advance must not duplicate');
    $_POST['loai']='thanh_toan'; $_POST['request_key']=bin2hex(random_bytes(16)); $_POST['tien_luong']='1000000'; $_POST['tien_nhien_lieu']='300000';
    u8_action('dt_finance_save');
    $payments=u8_query("select so_tien from #_dt_taichinh where id_hocvien in (?,?) and loai='thanh_toan'",array_values($ids));
    u8_check(count($payments)===2 && (int)$payments[0]['so_tien']===800000,'Net settlement must subtract 500000 advance per student');
    $_POST['request_key']=bin2hex(random_bytes(16)); u8_action('dt_finance_save',false);
    $_POST['ids']=array($studentIds['CE'][1]); u8_action('dt_finance_save',false);
    echo "PASS finance: selected advances, duplicate request, net settlement, duplicate settlement and cross-course rejection\n";
    $syncStudent=$d->rawQueryOne('select * from #_dt_hocvien where id=?',array($ids[2]));
    $syncTeacher=$tag.' BT teacher';
    u8_query('update #_dt_hocvien set he_daotao=?,gv_hoten=?,gv_key=? where id=?',array('BT',$syncTeacher,dt_gv_key($syncTeacher),$syncStudent['id']));
    $ce=$d->rawQueryOne('select * from #_dt_hocvien where id=?',array($studentIds['CE'][1]));
        $ceTeacher=$tag.' CE teacher';u8_query('update #_dt_hocvien set gv_hoten=?,gv_key=? where id=?',array($ceTeacher,dt_gv_key($ceTeacher),$ce['id']));$ce['gv_hoten']=$ceTeacher;
    u8_check(!dt_finance_eligible($ce,'ce'),'CE without DAT must not be eligible');
    u8_query('insert into #_dt_dat_override (id_khoa,cccd,dat) values (?,?,1)',array($ce['id_khoa'],$ce['cccd']));
    $ce['he_khoa']='DAT';
    u8_check(dt_finance_eligible($ce,'ce'),'CE manual DAT and DAT system must be eligible');
    $ce['he_daotao']='BT';
    u8_check(!dt_finance_eligible($ce,'ce'),'BT must not enter finance even when DAT has passed');
    $ce['he_daotao']='';
    $_POST=array('id_khoa'=>$courseIds['CE'],'group'=>'ce','csrf'=>'test','loai'=>'tam_ung','ngay'=>'2026-10-24','request_key'=>bin2hex(random_bytes(16)),'ids'=>array_values($studentIds['CE']),'so_tien_tam_ung'=>'500000');
    u8_action('dt_finance_save',false);
    u8_check(count(u8_query('select id from #_dt_taichinh where id_hocvien in (?,?)',array_values($studentIds['CE'])))===0,'Invalid selected CE must roll back earlier valid student');
    echo "PASS finance atomic rollback for mixed CE eligibility\n";
    $student=$d->rawQueryOne('select * from #_dt_hocvien where id=?',array($ids[1]));
    u8_query("insert into #_xd_hocvien (ho_ten,cccd,khoa,nhom,ngay_thanh_toan,so_tien_thanh_toan,id_bangke) values (?,?,'old','BT',null,0,0)",array($tag,$student['cccd']));
    $sync=xd_sync_training_students($bc);
    u8_check($sync['ok'],'DAT sync failed '.json_encode($sync,JSON_UNESCAPED_UNICODE).' DB='.json_encode($d->getLastError(),JSON_UNESCAPED_UNICODE));
    $fuel=$d->rawQueryOne('select * from #_xd_hocvien where cccd=?',array($student['cccd']));
    u8_check($fuel['ho_ten']===$tag && $fuel['nhom']==='BT' && $fuel['khoa']==='old','Unpaid XD record must remain unchanged');
    $btFuel=$d->rawQueryOne('select * from #_xd_hocvien where cccd=?',array($syncStudent['cccd']));
    u8_check($sync['xd_added']===1 && $btFuel['khoa']===$tag.'-B1' && $btFuel['nhom']==='BT' && $btFuel['gv_hoten']===$syncTeacher && $btFuel['gv_key']===dt_gv_key($syncTeacher),'DAT sync must use the real course code, teacher and BT group for a B1 BT student');
    u8_check($btFuel['ngay_thanh_toan']===null && (int)$btFuel['so_tien_thanh_toan']===0 && (int)$btFuel['id_bangke']===0,'New DAT sync must start unpaid and outside a payment batch');
    echo "PASS B1 BT sync uses course/teacher and starts unpaid\n";
    u8_query("update #_xd_hocvien set ho_ten=?,ngay_thanh_toan='2026-10-24',so_tien_thanh_toan=100 where id=?",array($tag.' paid',$fuel['id']));
    xd_sync_training_students($bc);
    $fuel=$d->rawQueryOne('select * from #_xd_hocvien where id=?',array($fuel['id']));
    u8_check($fuel['ho_ten']===$tag.' paid','Paid fuel records must be unchanged');
    u8_query("update #_dt_hocvien set he_daotao='BT' where id=?",array($studentIds['CE'][1]));
    $ceSync=xd_sync_training_students($courseIds['CE']);
    u8_check($ceSync['ok'] && $ceSync['xd_added']===1 && $ceSync['ce_added']===0,'Passed CE must enter normal XD list, not CE-only storage');
    u8_check(count(u8_query('select id from #_xd_hocvien where cccd=?',array($ce['cccd'])))===1,'CE missing from regular XD students');
    u8_check(count(u8_query('select id from #_xd_ce_hocvien where id_khoa=?',array($courseIds['CE'])))===0,'CE-only storage must not receive new DAT students');
        $ceFuel=$d->rawQueryOne('select * from #_xd_hocvien where cccd=?',array($ce['cccd']));
        u8_check($ceFuel['khoa']===$tag.'-CE' && $ceFuel['nhom']==='DAT' && $ceFuel['gv_hoten']===$ceTeacher && $ceFuel['ngay_thanh_toan']===null,'CE sync must use the real course/teacher and remain unpaid in regular XD');
    $_REQUEST=array('id_khoa'=>$courseIds['CE'],'group'=>'ce');
    dt_finance_list();
    u8_check(count($finance_students)===0,'BT student with DAT may enter XD but must not enter CK/DAT finance');
    $_POST=array('id_khoa'=>$courseIds['CE'],'group'=>'ce','csrf'=>'test','loai'=>'tam_ung','ngay'=>'2026-10-24','request_key'=>bin2hex(random_bytes(16)),'ids'=>array($studentIds['CE'][1]),'so_tien_tam_ung'=>'500000');
    u8_action('dt_finance_save',false);
    u8_check(count(u8_query('select id from #_dt_taichinh where id_hocvien=?',array($studentIds['CE'][1])))===0,'Direct request must not bypass CK/DAT system restriction');
    echo "PASS separate pipelines: DAT-qualified BT enters XD, but finance list/write reject BT\n";
    $other=$d->rawQueryOne('select * from #_dt_hocvien where id=?',array($studentIds['A1'][1]));
    u8_query('insert into #_dt_dat_override (id_khoa,cccd,dat) values (?,?,1)',array($other['id_khoa'],$other['cccd']));
    $otherSync=xd_sync_training_students($courseIds['A1']);
    u8_check($otherSync['ok'] && $otherSync['xd_added']===1 && $otherSync['not_passed']===1,'Every rank must sync only after DAT completion');
    $other['he_khoa']='CK';
    u8_check(dt_finance_eligible($other,'bc'),'Other rank with manual DAT and CK system must enter finance');
    $again=xd_sync_training_students($courseIds['CE']);
    u8_check($again['ok'] && $again['xd_added']===0,'Repeated sync must not duplicate CE students');
    echo "PASS DAT: all ranks, CE in regular XD, missing DAT blocked, old XD frozen and repeat sync idempotent\n";
    require_once LIBRARIES.'PHPExcel.php';
    $tuitionFile=sys_get_temp_dir().'/'.$tag.'.xlsx';
    foreach(array(true,false) as $withFees)
    {
        $book=new PHPExcel(); $sheet=$book->getActiveSheet();
        $headers=array('Mã đăng ký','CCCD','Họ và tên');
        $values=array($student['ma_hv'],$student['cccd'],$student['hoten']);
        if($withFees){$headers=array_merge($headers,array('Số tiền đã đóng','Số tiền còn thiếu'));$values=array_merge($values,array(15000000,2000000));}
        foreach($headers as $column=>$value) $sheet->setCellValueByColumnAndRow($column,1,$value);
        foreach($values as $column=>$value) $sheet->setCellValueExplicitByColumnAndRow($column,2,(string)$value,PHPExcel_Cell_DataType::TYPE_STRING);
        PHPExcel_IOFactory::createWriter($book,'Excel2007')->save($tuitionFile);
        $_REQUEST=array('id_khoa'=>$bc); $_POST=array();
        $_FILES=array('file-excel'=>array('name'=>'tuition.xlsx','tmp_name'=>$tuitionFile,'error'=>0));
        u8_action('dt_hocvien_upload_excel');
        $stored=$d->rawQueryOne('select hoc_phi_da_dong,hoc_phi_con_thieu from #_dt_hocvien where id=?',array($ids[1]));
        u8_check((int)$stored['hoc_phi_da_dong']===15000000 && (int)$stored['hoc_phi_con_thieu']===2000000,'Tuition import/preservation failed');
        $book->disconnectWorksheets();
    }
    unlink($tuitionFile);
    echo "PASS tuition import and absent-column preservation\n";
    if(!in_array('--payments-only',$argv,true))
    {
    foreach(array('moto','oto','oto') as $offset=>$kind)
    {
        u8_query('insert into #_dt_sh_ky (ten,ngay,loai,nguoi_nhap) values (?,?,?,?)',array($tag.'-'.$offset,'2026-10-'.(24+$offset),$kind,$tag));
        $periodIds[]=(int)$d->getLastInsertId();
        $files=glob($samples.($kind==='moto'?'/*.xls':'/*.xlsm'));
        u8_check(count($files)>0,'Missing real sample '.$kind);
        $_REQUEST=array(); $_POST=array('id_ky'=>end($periodIds));
        $_FILES=array('file-excel'=>array('name'=>basename($files[0]),'tmp_name'=>$files[0],'error'=>0));
        u8_action('dt_sathach_upload_excel');
        $rows=u8_query('select * from #_dt_sat_hach where id_ky=?',array(end($periodIds)));
        u8_check(count($rows)>100,'Real sample rows not imported');
        u8_check($rows[0]['noi_dung_sh']!=='' && $rows[0]['diem_lt']!=='','Highlighted fields missing');
        if($kind==='moto') u8_check($rows[0]['duong']==='' && $rows[0]['mp']==='','Motorcycle must not import car-only results');
        else u8_check($rows[0]['diem_mp']!=='' && $rows[0]['diem_duong']!=='','Car highlighted MP/road missing');
        echo 'PASS real '.$kind.' sample: '.count($rows)." rows, dated period and highlighted fields\n";
    }
    $first=u8_query('select cccd from #_dt_sat_hach where id_ky=? limit 1',array($periodIds[1]));
    $attempts=u8_query('select id from #_dt_sat_hach where cccd=? and id_ky in (?,?)',array($first[0]['cccd'],$periodIds[1],$periodIds[2]));
    u8_check(count($attempts)===2,'Separate exam attempts must both survive');
    $_REQUEST=array('ngay_sh'=>'2026-10-25'); dt_sathach_list();
    u8_check(count($items)>100,'Date filter failed');
    u8_check(!dt_sathach_pass_label('Rớt sát hạch (Đạt LT, Rớt MP)'),'Failed label wrongly passed');
    echo "PASS attempts, date lookup and failed-result labels\n";
    }
}
finally
{
    if(isset($tuitionFile) && is_file($tuitionFile)) unlink($tuitionFile);
    foreach($periodIds as $id){u8_query('delete from #_dt_sat_hach where id_ky=?',array($id));u8_query('delete from #_dt_sh_ky where id=?',array($id));}
    foreach($studentIds as $group) foreach($group as $id) u8_query('delete from #_dt_taichinh where id_hocvien=?',array($id));
    foreach($courseIds as $id)
    {
        $students=u8_query('select cccd from #_dt_hocvien where id_khoa=?',array($id));
        foreach($students as $student){u8_query('delete from #_xd_hocvien where cccd=?',array($student['cccd']));u8_query('delete from #_xd_ce_hocvien where cccd=? and id_khoa=?',array($student['cccd'],$id));}
        u8_query('delete from #_dt_dat_override where id_khoa=?',array($id));
        u8_query('delete from #_dt_dat_phien where id_khoa=?',array($id));
        u8_query('delete from #_dt_hocvien where id_khoa=?',array($id));
        u8_query('delete from #_dt_khoa where id=?',array($id));
    }
    u8_query('delete from #_dt_import_log where user=?',array($tag));
    echo "Test data cleaned\n";
}