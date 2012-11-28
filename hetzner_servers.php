<?php

/* Developed by: hostdog.gr */
/* Version: 0.1 */
/* Release Date: 09/10/2012 */

function widget_hetzner_servers($vars) {
  
  $key  = 'hostdog';
  
  if ($_POST['hetzner_reset']) { mysql_query("DELETE FROM hetzner_servers") or die(mysql_error()); }
  
  if ($_POST['hetzner_submit']) {
    $username = mysql_real_escape_string($_POST['h_user']);
    $password = mysql_real_escape_string($_POST['h_pass']);
    $password2 = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $password, MCRYPT_MODE_CBC, md5(md5($key))));
    mysql_query("DELETE FROM hetzner_servers") or die(mysql_error());      
    mysql_query('INSERT INTO hetzner_servers (h_user, h_pass) VALUES("'. $username .'", "'. $password2 .'") ') or die(mysql_error());      
  }

  $sql= "SELECT * FROM hetzner_servers";
  $result = @mysql_query($sql);
  $num_rows = @mysql_num_rows($result);
  if ($num_rows == 0) {
    $createsql = "CREATE TABLE hetzner_servers ( h_user varchar(30), h_pass varchar(128))";
    @mysql_query($createsql);  // Create table
    
    $content ='
    <form name="hetzner" action="'. $_SERVER['PHP_SELF']  .'" method="post">
      username: <input type="text" name="h_user">
      password: <input type="password" name="h_pass">
      <input type="submit" name="hetzner_submit" value="Submit">
    </form>
    ';     
    return array( 'title' => 'Hetzner Servers', 'content' => $content);
  }

  $row = mysql_fetch_array($result) or die(mysql_error());
  $username =  $row['h_user'];
  $password = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($row['h_pass']), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
    
    $list = json_decode(docurl('https://robot-ws.your-server.de/server', $username, $password));
    
    // $allips = json_decode(docurl('https://robot-ws.your-server.de/ip', $username, $password));
    // $alltraffic = json_decode(docurl('https://robot-ws.your-server.de/traffic?type=month&from=2012-09-01&to=2012-09-31&ip=78.46.85.39', $username, $password));
    
    foreach($list as $allserv) {
      $ip = $allserv->server->server_ip;
      $traffic = json_decode(docurl('https://robot-ws.your-server.de/traffic?type=month&from='. date("Y-m") .'-01&to='. date("Y-m-d") .'&ip='. $allserv->server->server_ip, $username, $password));
    
      if ($allserv->server->status == 'ready') { $stcolor = '#84B429'; } else { $stcolor = '#CC0000'; }
    
      $table .='
      <tr>
        <td style="background-color: #fff; font-weight: bold;">'. $allserv->server->server_ip .'</td>
        <td style="background-color: #fff;text-align: left;">'. $allserv->server->server_name .'</td>
        <td style="background-color: #fff;text-align: center;">'. $allserv->server->product .'</td>
        <td style="background-color: #fff;text-align: right;">'. $allserv->server->dc .'</td>
        <td style="background-color: #fff;text-align: center; color: '. $stcolor .'; font-weight: bold;">'. $allserv->server->status .'</td>
        <td style="background-color: #fff;text-align: right;">'. $allserv->server->paid_until .'</td>
        <td style="background-color: #fff;text-align: center;">'. round($traffic->traffic->data->$ip->in,2) .'</td>
        <td style="background-color: #fff;text-align: center;">'. round($traffic->traffic->data->$ip->out,2) .'</td>
        <td style="background-color: #fff;text-align: center;">'. round($traffic->traffic->data->$ip->sum,2) .'</td>
        <td style="background-color: #fff;text-align: center;">'. $allserv->server->traffic .'</td>
      </tr>
      ';
    }
    
    $content = print_r($tableExists, true).'<table cellspacing=1 width="100%" style="background-color: #ccc;">
    <tr  style="border: none;">
      <td colspan="6" style="background-color: #efefef;"></td>
      <td colspan="4" style="text-align: center; background-color: #efefef; font-weight: bold; ">Bandwidth</td>
    </tr>
    <tr style="text-align: center; background-color: #efefef; font-weight: bold; ">
        <td style="text-align: left;" >IP</td>
        <td style="text-align: left;">Server Name</td>
        <td style="">Type</td>
        <td style="text-align: right;">DC</td>
        <td style="">Status</td>
        <td style="">Due Date</td>
        <td style="">In</td>
        <td style="">Out</td>
        <td style="">Total</td>
        <td style="">Limit</td>
      </tr>'.
      $table 
    .'</table>
    <br />
    <center>
    <form name="hetzner" action="'. $_SERVER['PHP_SELF']  .'" method="post" style="display:inline;">
      <input type="submit" name="hetzner_reset" class="btn btn-danger" value="Reset Password">
    </form>
    <form name="hetzner" action="https://robot.your-server.de/" method="get" target="_blank" style="display:inline;">
      <input type="submit" name="go_to_robot" class="btn btn-success" value="Go to Robot">
    </form>
    </center>
    ';
    
    return array( 'title' => 'Hetzner Servers', 'content' => $content); //.'<pre>'.print_r($alltraffic, true).'</pre>'
 
}
 
add_hook("AdminHomeWidgets",1,"widget_hetzner_servers");

function docurl($url, $serverusername, $serverpassword, $ref='') {

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_USERPWD, "$serverusername:$serverpassword");
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  //curl_setopt($ch, CURLOPT_REFERER, 'ns1.hostdog.gr');
  //curl_setopt($curl, CURLOPT_AUTOREFERER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  $output = curl_exec($ch);
  
  $info = curl_getinfo($ch);
  curl_close($ch);

  return $output;
 
}
 
?>
