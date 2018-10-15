<?php
$setting = C::t('common_setting')->fetch_all(array('bbssdk_setting'));
$setting = (array)unserialize($setting['bbssdk_setting']);

$logo    = $setting['logo'] ? '请上传 80 x 80 像素的图标<br/><img src="'.$setting['logo'].'" width="80" height="80" />' : '请上传 80 x 80 像素的图标';
$qrcode  = $setting['qrcode'] ? '您可以制作安卓、iOS双下载链接的二维码并上传，默认为安卓下载链接<br/>（二维码尺寸推荐 100 x 100 像素）<br/><img src="'.$setting['qrcode'].'" width="100" height="100"  />' : '您可以制作安卓、iOS双下载链接的二维码并上传，默认为安卓下载链接<br/>（二维码尺寸推荐 100 x 100 像素）<br/><img src="plugin.php?id=bbssdk:qrcode" width="100" height="100"  />';
$preview = $setting['preview'] ? '请上传 240 x 418 像素的预览图<br/><img src="'.$setting['preview'].'" width="240" height="418" />' : '请上传 240 x 418 像素的预览图';

if(!submitcheck('ss')) {
    $setting['template'] = $setting['template']?$setting['template']:1;
    showformheader('plugins&operation=config&do='.$pluginid.'&identifier=bbssdk&pmod=downloadsetting', 'enctype');
    showtableheader();
    showsetting('应用名称<font color="red">*</font>', 'setting[app_name]', $setting['app_name'], 'text','',0,'','id = appname');
    showsetting('Slogan<font color="red">*</font>', 'setting[slogan]', $setting['slogan'], 'text','',0,'一句话描述您的APP吧（20字内）','id = slogan');
    showsetting('iOS-版本号', 'setting[ios_version]', $setting['ios_version'], 'text');
    showsetting('iOS-包体大小', 'setting[ios_size]', $setting['ios_size'], 'text');
    showsetting('Android-版本号', 'setting[android_version]', $setting['android_version'], 'text');
    showsetting('Android-包体大小', 'setting[android_size]', $setting['android_size'], 'text');
    showsetting('应用图标<font color="red">*</font>', 'logo', $setting['logo'], 'file', 0, 0, $logo,'id = logo');
    showsetting('二维码', 'qrcode', $setting['qrcode'], 'file', 0, 0, $qrcode);
    echo '<tr><td colspan="2" class="td27" s="1">模板<font color="red">*</font></td></tr>';
    echo '<tr class="noborder">'
            . '<td class="vtop rowform" style="width:400px">'
            . '<ul onmouseover="altStyle(this);">'
            . '<li class="'.($setting['template']==1?'checked':'').'"><input class="radio" type="radio" name="setting[template]" value="1" '.($setting['template']==1?'checked':'').'>模板一<br/><br/><img width="300px;" src="./source/plugin/bbssdk/template/assets/1.jpg"><br/><br/></li>'
            . '<li class="'.($setting['template']==2?'checked':'').'"><input class="radio" type="radio" name="setting[template]" value="2" '.($setting['template']==2?'checked':'').'>模板二<br/><br/><img width="300px;" src="./source/plugin/bbssdk/template/assets/2.jpg"><br/><br/></li>'
            . '<li class="'.($setting['template']==3?'checked':'').'"><input class="radio" type="radio" name="setting[template]" value="3" '.($setting['template']==3?'checked':'').'>模板三<br/><br/><img width="300px;" src="./source/plugin/bbssdk/template/assets/3.jpg"><br/><br/></li>'
            . '</ul>'
            . '</td>'
        . '</tr>';
    showsetting('上传APP效果图', 'preview', $setting['preview'], 'file', 0, 0, $preview);
    showsetting('iOS下载地址<font color="red">*</font>', 'setting[ios_addr]', $setting['ios_addr'], 'text','',0,'请输入您的应用在App Store中的地址','id = iOS_download');
    showsetting('Android下载地址', 'setting[android_addr]', $setting['android_addr'], 'text','',0,'如未填写，则默认为mob提供的下载地址');
    echo '<tr>'
    . '<td colspan="1"><div class="fixsel"><input type="button" class="btn" id="submit_settingsubmit" name="settingsubmit" onclick="check_submit()" title="按 Enter 键可随时提交您的修改" value="确认">'
            . '   <input type="button" class="btn" id="preview" name="settingsubmit" onclick="window.open(\'plugin.php?id=bbssdk:download\')" value="生成"></div></td>'
            . '   <input type="hidden" name="ss" value="1">'
            . '</tr>';
    showtablefooter();
    showformfooter();
    echo "<script>
    function check_submit(){
        var logo_v = '".$setting['logo']."';
        if(!document.getElementById('appname').value){
            alert('请填写应用名称');return;
        }
        if(!document.getElementById('slogan').value){
            alert('请填写应用的Slogan');return;
        }
        if(!document.getElementById('logo').value&&!logo_v){
            alert('请为应用上传图标');return;
        }
        if(!document.getElementById('iOS_download').value){
            alert('请填写iOS下载地址');return;
        }
        document.getElementById('cpform').submit();
        
    }
    
    </script>";
}else{
    if(!trim($_POST['setting']['app_name'])){
        cpmsg_error('应用名称不能为空');
    }
    if(!trim($_POST['setting']['slogan'])){
        cpmsg_error('Slogan不能为空');
    }
    if(!trim($setting['logo'])&&!$_FILES['logo']['tmp_name']){
        cpmsg_error('请上传应用图标');
    }
    if(!trim($_POST['setting']['ios_addr'])){
        cpmsg_error('请设置iOS下载地址');
    }

    if($_FILES['logo']['tmp_name']) {
            $upload = new discuz_upload();
            if(!$upload->init($_FILES['logo'], 'common', random(3, 1), random(8)) || !$upload->save()) {
                    cpmsg($upload->errormessage(), '', 'error');
            }
            $parsev = parse_url($_G['setting']['attachurl']);
            $_GET['setting']['logo'] = ($parsev['host'] ? '' : $_G['siteurl']).$_G['setting']['attachurl'].'common/'.$upload->attach['attachment'];
    } else {
            $_GET['setting']['logo'] = $setting['logo'];
    }
    
    if($_FILES['qrcode']['tmp_name']) {
            $upload = new discuz_upload();
            if(!$upload->init($_FILES['qrcode'], 'common', random(3, 1), random(8)) || !$upload->save()) {
                    cpmsg($upload->errormessage(), '', 'error');
            }
            $parsev = parse_url($_G['setting']['attachurl']);
            $_GET['setting']['qrcode'] = ($parsev['host'] ? '' : $_G['siteurl']).$_G['setting']['attachurl'].'common/'.$upload->attach['attachment'];
    } else {
            $_GET['setting']['qrcode'] = $setting['qrcode'];
    }
    
    if($_FILES['preview']['tmp_name']) {
            $upload = new discuz_upload();
            if(!$upload->init($_FILES['preview'], 'common', random(3, 1), random(8)) || !$upload->save()) {
                    cpmsg($upload->errormessage(), '', 'error');
            }
            $parsev = parse_url($_G['setting']['attachurl']);
            $_GET['setting']['preview'] = ($parsev['host'] ? '' : $_G['siteurl']).$_G['setting']['attachurl'].'common/'.$upload->attach['attachment'];
    } else {
            $_GET['setting']['preview'] = $setting['preview'];
    }
    
    $setting = $_POST['setting'];
    C::t('common_setting')->update_batch(array('bbssdk_setting'=>($_GET['setting'] + $setting)));
    cpmsg('setting_update_succeed', 'action=plugins&operation=config&do='.$pluginid.'&identifier=bbssdk&pmod=downloadsetting', 'succeed');
}