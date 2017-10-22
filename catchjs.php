<?php
/*
******************************************
**Catchjs 1.0*****************************
**Catchjs will log all javascript errors**
**Created by Harish U Warrier*************
**Created on 15-10-2017*******************
**Modified on 21-10-2017******************
**huwz1it@gmail.com***********************
******************************************
*/
class catchjs {
    private $db;
    private $dbh;
    public $lt=10;
    public $version="v 1.0";
    public function __construct() {
        $this->db="sqlite:catchjs.db";       
    }
    function hd(){
        header('Access-Control-Allow-Origin: *'); 
        header('Access-Control-Allow-Methods: GET, PUT, POST');
        header("Content-Type: application/javascript");
    }
    function js(){
        $url= $this->getsettings("ajaxurl");
        $url=$url!=""?$url:"catchjs.php";
        $this->hd();        
        ?>
        try{
            window.onerror=function(a,b,c,d,e){
                xhttp = new XMLHttpRequest();
                xhttp.open("POST", "<?php echo $url;?>?t=s", true);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhttp.send("a="+a+"&b="+b+"&c="+c+"&d="+d+"&u="+window.location.href);                 
            };
        }catch(e){
            console.log("this plugin is not supported",e);
        }
        <?php
    }
    function initdb(){   
        $this->dbh  = new PDO($this->db) or die("cannot open the database");
        $this->dbh->exec("CREATE TABLE IF NOT EXISTS catchjs (
            id INTEGER PRIMARY KEY AUTOINCREMENT   UNIQUE,   
            error VARCHAR( 400 ),
            page VARCHAR( 400 ),
            url VARCHAR( 400 ),
            line VARCHAR( 50 ),
            col VARCHAR( 50 ),
            ip VARCHAR(100),
            agent TEXT,
            misc TEXT,
            date DATETIME DEFAULT CURRENT_TIMESTAMP
        );");
        $this->dbh->exec("CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT   UNIQUE,   
            sname VARCHAR( 400 ),       
            svalue TEXT,            
            date DATETIME DEFAULT CURRENT_TIMESTAMP
        );");
    }
    function get($s=0,$l=10){
        $this->initdb();
        $this->lt=$l;
        $lim="LIMIT $s,$l";
        if($l==0){
            $lim="";
        }
        $query =  "SELECT * FROM catchjs order by date DESC $lim";
        $sth=$this->dbh->prepare($query);
        $sth->execute();
        $result=array();
        while ($row= $sth->fetch(PDO::FETCH_ASSOC)){
            $row['ua']= isset($row['agent'])?$this->getUa($row['agent']):"";
            $row['uad']= _rt($row['ua']['f']);
            $ur= urlencode($row['error']);
            $st="<br/><a href='https://stackoverflow.com/search?q=".$ur."' target='_blank'>Stackoverflow</a>";
            $st.="&nbsp;&nbsp;<a href='https://www.google.co.in/search?q=".$ur."'  target='_blank' >Google</a>";
            $row['er']=$row['error']=="Script error."?"Couldnt catch this error please check console":$row['error'].$st;
            $row['link']="<a class='link' href='".$row['url']."' target='_blank'><span >&#x2794;</span></a>";
            $result[]=$row;
        }        
        return $result;
    }    
    function set(){
        $e=filter_input(INPUT_POST, "a");
        $p=filter_input(INPUT_POST, "b");
        $u=filter_input(INPUT_POST, "u");
        $l=filter_input(INPUT_POST, "c");
        $col=filter_input(INPUT_POST, "d");
        $a=filter_input(INPUT_SERVER,"HTTP_USER_AGENT");
        $ip=filter_input(INPUT_SERVER,"REMOTE_ADDR");
        $this->initdb();
        $this->dbh->exec('INSERT into catchjs (error,page,url,line,col,ip,agent) values("'.$e.'","'.$p.'","'.$u.'","'.$l.'","'.$col.'","'.$ip.'","'.$a.'")');
    }
    function setsettings($k,$v){
        $this->initdb();
        $qry='INSERT into settings (sname,svalue) values("'.$k.'","'.$v.'")';
        $old= $this->getsettings($k);
        if($old!=""){
            $qry='UPDATE settings set svalue="'.$v.'" WHERE sname="'.$k.'" ';
        }
        $this->dbh->exec($qry);
    }
    function getsettings($set){
        $this->initdb();   
        $query="SELECT svalue FROM settings WHERE sname='".$set."'";
        $sth=$this->dbh->prepare($query);
        $sth->execute();       
        $row= $sth->fetch(PDO::FETCH_ASSOC);
        return isset($row['svalue'])?$row['svalue']:""; 
    }
    function del($id){
        $this->initdb();   
        $query="DELETE FROM catchjs WHERE id='".$id."'";
        $sth=$this->dbh->prepare($query);
        $sth->execute();       
        return $sth->rowCount();
    }
    function delall(){       
        $this->initdb();   
        $query="DELETE FROM catchjs;";
        $sth=$this->dbh->prepare($query);
        $sth->execute();       
        return $sth->rowCount();
    }
    function tot(){
        $this->initdb();        
        $query =  "SELECT count(*) as t FROM catchjs";
        $sth=$this->dbh->prepare($query);
        $sth->execute();       
        $row= $sth->fetch(PDO::FETCH_ASSOC);
        return isset($row['t'])?$row['t']:0;        
    }
    function getEr($id){
        $this->initdb();        
        $query =  "SELECT error FROM catchjs WHERE id=$id";
        $sth=$this->dbh->prepare($query);
        $sth->execute();       
        $row= $sth->fetch(PDO::FETCH_ASSOC);
        return isset($row['error'])?$row['error']:0;       
    }
    function v(){
        $purl=filter_input(INPUT_POST, "cjs_url");
        if($purl!=NULL && $purl!=""){
            $this->setsettings("ajaxurl",$purl);
        }
        $t= $this->tot();
        $p= ceil($t/$this->lt);
        $f=filter_input(INPUT_COOKIE, "nofetch");
        $er=array();
        if($f=="1"){
            $er= $this->get(0,0);
            $p=0;
        }
        $url= $this->getsettings("ajaxurl");
        $url=$url!=""?$url:"catchjs.php";
    ?>
<!DOCTYPE HTML>
<html>
    <head>
        <title>Catch Js <?= $this->version?></title>
        <style>
            .pagination a {
                color: black;
                float: left;
                padding: 8px 16px;
                text-decoration: none;
                transition: background-color .3s;
                margin-top: 10px;
            }
            .pagination a.active {
                background-color: #4CAF50;
                color: white;
            }
            .pagination a:hover:not(.active) {background-color: #ddd;}
            thead{text-align: left;}
            table {
                border-collapse: collapse;
                width: 100%;
            }
            th, td {
                text-align: left;
                padding: 8px;
            }
            th {
                background-color: #666666;
                color: white;
            }
            tr:nth-child(even){background-color: #f2f2f2}
            a{color:blue;}
            input[type=text]{    
                padding: 5px 5px;
                margin: 8px 0;   
                border: 1px solid #ccc;
                border-radius: 4px;
                box-sizing: border-box;
            }
            .button {
                background-color: #555555; 
                border: none;
                color: white;
                padding: 5px 17px;
                text-align: center;
                text-decoration: none;                
                font-size: 13px;
                cursor: pointer;
            }
            .link{                
                text-decoration: none;
                padding-left: 5px;
            }
            .btnsmall{
                background-color: #555555;
                border: none;
                color: white;
                padding: 3px 7px;
                text-align: center;
                text-decoration: none;
                font-size: 10px;
                cursor: pointer;
                border-radius: 5px;
            }
        </style>
        <script>
            function setCookie(b, f) {
                var e = new Date();var c = 365;
                if (arguments.length>2) {
                    c = arguments[2];
                }
                e.setTime(e.getTime() + (c * 24 * 60 * 60 * 1000));
                var a = "expires=" + e.toUTCString();
                document.cookie = b + "=" + f + "; " + a+";path=/;";
            }
            function getCookie(d) {
                var b = d + "=";var a = document.cookie.split(";");
                for (var e = 0; e < a.length; e++) {
                    var f = a[e];
                    while (f.charAt(0) == " ") {
                        f = f.substring(1);
                    }
                    if (f.indexOf(b) != -1) {
                        return f.substring(b.length, f.length);
                    }
                }
                return"";
            }            
            if(typeof fetch=="undefined" && getCookie("nofetch")==""){
                setCookie("nofetch","1");
                window.location.reload();
            }
        </script>
    </head>
    <body>
        <div style="margin: 5px;">
            <form id="save_form" method="post">
                <strong>Script Url</strong> <input name="cjs_url" type="text" value="<?php echo$url;?>" id="cjs_url"/>
                <button class="button" id="save_cjs_url">Update</button>
                <button type="submit" class="button" id="defaut_cjs_url" onclick="cjs_default();">Default</button>
                <?php if($f!="1"){?>
                <a class="btnsmall" style="float: right" onclick="delete_item('')">Delete all</a>
                <?php }?>
            </form>            
        </div>        
        <div style="overflow-x:auto;">
            <table >
                <thead><tr><th>Error</th><th>Page</th><th>Line</th><th>Column</th><th>Url</th><th>Ip</th><th>Agent</th><th>Date</th>
                    <?php if($f!="1"){echo"<th></th>";}?>
                </tr></thead>
                <tbody id="ertbl">
                    <?php foreach ($er as $e){
                        echo"<tr>";
                        echo"<td>".$e['er']."</td>";
                        echo"<td>".$e['page']."</td>";
                        echo"<td>".$e['line']."</td>";
                        echo"<td>".$e['col']."</td>";
                        echo"<td>".$e['url'].$e['link']."</td>";                        
                        echo"<td>".$e['ip']."</td>";
                        echo"<td>".$e['uad']."</td>";
                        echo"<td>".$e['date']."</td>";
                        echo"</tr>";
                    }
                    if(count($er)==0){echo"<tr><td colspan='6'><strong>Congragulations!!! no errors till now...</strong></td></tr>";}
                    ?>
                </tbody>
            </table>
        </div>
        <div class="pagination">
        <?php for($i=0;$i<$p;$i++){?>
            <a href="javascript:void(0);" class="pagebtn" data-limit="<?=(($i)*$this->lt)?>"><?php echo$i+1;?></a>
        <?php }?>
        </div>
        <a href="" style="display: none;" id="hidden_link" target="_blank"></a>
    </body>
    <script>      
        window.addEventListener('load',function(){
            var btn=document.querySelectorAll('.pagebtn');
            for(var i=0;i<btn.length;i++){
                btn[i].onclick=function(){
                    var lt=this.dataset.limit; 
                    for(var j=0;j<btn.length;j++){
                       btn[j].classList.remove('active'); 
                    }
                    this.classList.add('active');
                    fetch("<?php echo$url;?>?t=json&lt="+lt).then(function(l){return l.json();}).then(function(res){
                        var tbl=document.getElementById('ertbl');
                        if(res.length==0){
                            tbl.innerHTML="<tr><td colspan='6'><strong>Congragulations!!! no errors till now...</strong></td></tr>";
                        }else{
                           var html="";
                           for(var r in res){
                               var tr="<tr>";
                               tr+="<td>"+res[r].er+"</td>";
                               tr+="<td>"+res[r].page+"</td>";
                               tr+="<td>"+res[r].line+"</td>";
                               tr+="<td>"+res[r].col+"</td>";
                               tr+="<td>"+res[r].url+res[r].link+"</td>";                              
                               tr+="<td>"+res[r].ip+"</td>";
                               tr+="<td>"+res[r].uad+"</td>";
                               tr+="<td>"+res[r].date+"</td>";
                               tr+="<td><a href='javascript:void(0);' class='btnsmall' onclick='delete_item("+res[r].id+")'>Delete</a></td>";
                               tr+="</tr>";
                               html+=tr;
                           }
                           tbl.innerHTML=html;
                        }
                    });
                };
            }
            if(document.querySelector('.pagebtn')!=null){
                document.querySelector('.pagebtn').click();
            }else if(getCookie("nofetch")==""){
                var tbl=document.getElementById('ertbl');                        
                tbl.innerHTML="<tr><td colspan='6'><strong>Congragulations!!! no errors till now...</strong></td></tr>";                        
            }
        });
        function cjs_default(){
            document.querySelector("#cjs_url").value="catchjs.php";
            document.querySelector('#save_form').submit();
        }
        function delete_item($id){
            var params={};
            if(confirm("Are you sure?")){
                if($id===""){
                    if(confirm("This will delete all errors?")){
                        params="da=1";
                    }
                }else{
                    params="id="+$id;
                }   
                if(Object.keys(params).length>0){
                    fetch("<?php echo$url;?>?t=del&"+params).then(function(){
                        window.location.reload();
                        if($id===""){
                            
                        }else{
                        
                        }
                    });
                }
            }
        }        
    </script>
</html>
    <?php
    }
    function getUa($agent = null){
        $u_agent = ($agent!=null)? $agent : $_SERVER['HTTP_USER_AGENT']; 
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version= "";       
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        }elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        }elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }       
        if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) { 
            $bname = 'Internet Explorer'; 
            $ub = "MSIE"; 
        }elseif(preg_match('/Edge/i',$u_agent)){ 
            $bname = 'Microsoft Edge'; 
            $ub = "Edge"; 
        }elseif(preg_match('/Firefox/i',$u_agent)){ 
            $bname = 'Firefox'; 
            $ub = "Firefox"; 
        }elseif (preg_match('/Internet Explorer/i',$u_agent) || preg_match('/Trident.* rv/i',$u_agent)) {
            $bname = 'Internet Explorer'; 
            $ub = "Trident.* rv";             
        }elseif(preg_match('/Chrome/i',$u_agent)){ 
            $bname = 'Chrome'; 
            $ub = "Chrome"; 
        }elseif(preg_match('/Safari/i',$u_agent)){ 
            $bname = 'Safari'; 
            $ub = "Safari"; 
        }elseif(preg_match('/Opera/i',$u_agent)){ 
            $bname = 'Opera'; 
            $ub = "Opera"; 
        }elseif(preg_match('/Netscape/i',$u_agent)){ 
            $bname = 'Netscape'; 
            $ub = "Netscape"; 
        }else{
            $bname="";
            $ub="";
        } 
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known).')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {  
            $pattern='|Trident.* rv.*?([0-9\.]+)|i';
            if(!preg_match($pattern, $u_agent, $matches)){
                $matches['browser']=array();
                $matches['version']=array();
            }else{
                $matches['browser']=$matches;
                $matches['version']=$matches;
            }     
        }   
        $i = count($matches['browser']);
        if ($i != 1) {            
            if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
                $version= _rt($matches['version'][0]);
            }else {
                $version= _rt($matches['version'][1]);
            }
        }else {
            $version= $matches['version'][0];
        }       
        if ($version==null || $version=="") {$version="?";}
        $os=$this->getOS($agent);
        return array(
            'u' => $u_agent,
            'b'      => $bname,
            'v'   => $version,
            'p'  => $platform,
            'o'=>$os ,
            "f"=>$bname."-".$version."($os)"
        );   
    }
    function getOS($user_agent) {     
        $os_platform="Unknown OS Platform";
        $os_array= array(
            '/windows nt 10/i'     =>  'Windows 10',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        );
        foreach ($os_array as $regex => $value) { 
            if (preg_match($regex, $user_agent)) {
                $os_platform    =   $value;
            }
        }
        return $os_platform;
    }
}
$cj=new catchjs();
$type= filter_input(INPUT_GET, "t");
switch($type){
    case"js":
        $cj->js();
        break;
    case "s":   
        $cj->set();
        break;
    case"v":
        $cj->v();
        break;
    case "json":
        $lt=filter_input(INPUT_GET, "lt");
        $res=$cj->get($lt);
        echo json_encode($res);
        break;    
    case "del":     
        $id=filter_input(INPUT_GET, "id");
        $da=filter_input(INPUT_GET, "da");
        if($id!=null && $id!=""){
            $cj->del($id);
        }else if($da=="1"){
            $cj->delall();
        }       
        break;    
    default:
        echo $cj->v();       
}
function _rt(&$val,$def="",$null=false){
    if(isset($val)&&$null==true&&($val==""||$val==null))
        return $def;
    else if(isset($val))
        return $val;
    else
        return $def;
}