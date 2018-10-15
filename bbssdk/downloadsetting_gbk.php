<?php
$setting = C::t('common_setting')->fetch_all(array('bbssdk_setting'));
$setting = (array)unserialize($setting['bbssdk_setting']);

$logo    = $setting['logo'] ? '���ϴ� 80 x 80 ���ص�ͼ��<br/><img src="'.$setting['logo'].'" width="80" height="80" />' : '���ϴ� 80 x 80 ���ص�ͼ��';
$qrcode  = $setting['qrcode'] ? '������������׿��iOS˫�������ӵĶ�ά�벢�ϴ���Ĭ��Ϊ��׿��������<br/>����ά��ߴ��Ƽ� 100 x 100 ���أ�<br/><img src="'.$setting['qrcode'].'" width="100" height="100"  />' : '������������׿��iOS˫�������ӵĶ�ά�벢�ϴ���Ĭ��Ϊ��׿��������<br/>����ά��ߴ��Ƽ� 100 x 100 ���أ�<br/><img src="plugin.php?id=bbssdk:qrcode" width="100" height="100"  />';
$preview = $setting['preview'] ? '���ϴ� 240 x 418 ���ص�Ԥ��ͼ<br/><img src="'.$setting['preview'].'" width="240" height="418" />' : '���ϴ� 240 x 418 ���ص�Ԥ��ͼ';

if(!submitcheck('ss')) {
    $setting['template'] = $setting['template']?$setting['template']:1;
    showformheader('plugins&operation=config&do='.$pluginid.'&identifier=bbssdk&pmod=downloadsetting', 'enctype');
    showtableheader();
    showsetting('Ӧ������<font color="red">*</font>', 'setting[app_name]', $setting['app_name'], 'text','',0,'','id = appname');
    showsetting('Slogan<font color="red">*</font>', 'setting[slogan]', $setting['slogan'], 'text','',0,'һ�仰��������APP�ɣ�20���ڣ�','id = slogan');
    showsetting('iOS-�汾��', 'setting[ios_version]', $setting['ios_version'], 'text');
    showsetting('iOS-�����С', 'setting[ios_size]', $setting['ios_size'], 'text');
    showsetting('Android-�汾��', 'setting[android_version]', $setting['android_version'], 'text');
    showsetting('Android-�����С', 'setting[android_size]', $setting['android_size'], 'text');
    showsetting('Ӧ��ͼ��<font color="red">*</font>', 'logo', $setting['logo'], 'file', 0, 0, $logo,'id = logo');
    showsetting('��ά��', 'qrcode', $setting['qrcode'], 'file', 0, 0, $qrcode);
    echo '<tr><td colspan="2" class="td27" s="1">ģ��<font color="red">*</font></td></tr>';
    echo '<tr class="noborder">'
            . '<td class="vtop rowform" style="width:400px">'
            . '<ul onmouseover="altStyle(this);">'
            . '<li class="'.($setting['template']==1?'checked':'').'"><input class="radio" type="radio" name="setting[template]" value="1" '.($setting['template']==1?'checked':'').'>ģ��һ<br/><br/><img width="300px;" src="./source/plugin/bbssdk/template/assets/1.jpg"><br/><br/></li>'
            . '<li class="'.($setting['template']==2?'checked':'').'"><input class="radio" type="radio" name="setting[template]" value="2" '.($setting['template']==2?'checked':'').'>ģ���<br/><br/><img width="300px;" src="./source/plugin/bbssdk/template/assets/2.jpg"><br/><br/></li>'
            . '<li class="'.($setting['template']==3?'checked':'').'"><input class="radio" type="radio" name="setting[template]" value="3" '.($setting['template']==3?'checked':'').'>ģ����<br/><br/><img width="300px;" src="./source/plugin/bbssdk/template/assets/3.jpg"><br/><br/></li>'
            . '</ul>'
            . '</td>'
        . '</tr>';
    showsetting('�ϴ�APPЧ��ͼ', 'preview', $setting['preview'], 'file', 0, 0, $preview);
    showsetting('iOS���ص�ַ<font color="red">*</font>', 'setting[ios_addr]', $setting['ios_addr'], 'text','',0,'����������Ӧ����App Store�еĵ�ַ','id = iOS_download');
    showsetting('Android���ص�ַ', 'setting[android_addr]', $setting['android_addr'], 'text','',0,'��δ��д����Ĭ��Ϊmob�ṩ�����ص�ַ');
    echo '<tr>'
    . '<td colspan="1"><div class="fixsel"><input type="button" class="btn" id="submit_settingsubmit" name="settingsubmit" onclick="check_submit()" title="�� Enter ������ʱ�ύ�����޸�" value="ȷ��">'
            . '   <input type="button" class="btn" id="preview" name="settingsubmit" onclick="window.open(\'plugin.php?id=bbssdk:download\')" value="����"></div></td>'
            . '   <input type="hidden" name="ss" value="1">'
            . '</tr>';
    showtablefooter();
    showformfooter();
    echo "<script>
    function check_submit(){
        var logo_v = '".$setting['logo']."';
        if(!document.getElementById('appname').value){
            alert('����дӦ������');return;
        }
        if(!document.getElementById('slogan').value){
            alert('����дӦ�õ�Slogan');return;
        }
        if(!document.getElementById('logo').value&&!logo_v){
            alert('��ΪӦ���ϴ�ͼ��');return;
        }
        if(!document.getElementById('iOS_download').value){
            alert('����дiOS���ص�ַ');return;
        }
        document.getElementById('cpform').submit();
        
    }
    
    </script>";
}else{
    if(!trim($_POST['setting']['app_name'])){
        cpmsg_error('Ӧ�����Ʋ���Ϊ��');
    }
    if(!trim($_POST['setting']['slogan'])){
        cpmsg_error('Slogan����Ϊ��');
    }
    if(!trim($setting['logo'])&&!$_FILES['logo']['tmp_name']){
        cpmsg_error('���ϴ�Ӧ��ͼ��');
    }
    if(!trim($_POST['setting']['ios_addr'])){
        cpmsg_error('������iOS���ص�ַ');
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