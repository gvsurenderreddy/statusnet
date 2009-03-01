<?
define('INSTALLDIR', dirname(__FILE__));

function main()
{
    checkPrereqs();
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        handlePost();
    } else {
        showForm();
    }
}

function checkPrereqs()
{
}

function showForm()
{
?>
<p>Enter your database connection information below to initialize the database.</p>
<form method='post' action='install.php'>
	<fieldset>
	<ul class='form_data'>
	<li>
	<label for='sitename'>Site name</label>
	<input type='text' id='sitename' name='sitename' />
	<p>The name of your site</p>
	</li>
	<li>
	<li>
	<label for='host'>Hostname</label>
	<input type='text' id='host' name='host' />
	<p>Database hostname</p>
	</li>
	<li>
	<label for='host'>Database</label>
	<input type='text' id='database' name='database' />
	<p>Database name</p>
	</li>
	<li>
	<label for='username'>Username</label>
	<input type='text' id='username' name='username' />
	<p>Database username</p>
	</li>
	<li>
	<label for='password'>Password</label>
	<input type='password' id='password' name='password' />
	<p>Database password</p>
	</li>
	</ul>
	<input type='submit' name='submit' value='Submit'>
	</fieldset>
</form>
<?
}

function updateStatus($status, $error=false)
{
?>
	<li>
<?
    print $status;
?>
	</li>
<?
}

function handlePost()
{
?>
	<ul>
<?
    $host = $_POST['host'];
    $database = $_POST['database'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $sitename = $_POST['sitename'];

    updateStatus("Starting installation...");
    updateStatus("Checking database...");
    $conn = mysql_connect($host, $username, $password);
    if (!$conn) {
        updateStatus("Can't connect to server '$host' as '$username'.", true);
        showForm();
        return;
    }
    updateStatus("Changing to database...");
    $res = mysql_select_db($database, $conn);
    if (!$res) {
        updateStatus("Can't change to database.", true);
        showForm();
        return;
    }
    updateStatus("Running database script...");
    $res = runDbScript(INSTALLDIR.'/db/laconica.sql', $conn);
    if ($res === false) {
        updateStatus("Can't run database script.", true);
        showForm();
        return;
    }
    updateStatus("Writing config file...");
    $sqlUrl = "mysqli://$username:$password@$host/$database";
    $res = writeConf($sitename, $sqlUrl);
    if (!$res) {
        updateStatus("Can't write config file.", true);
        showForm();
        return;
    }
    updateStatus("Done!");
?>
	</ul>
<?
}

function writeConf($sitename, $sqlUrl)
{
    $res = file_put_contents(INSTALLDIR.'/config.php',
                             "<?\n".
                             "\$config['site']['name'] = \"$sitename\";\n\n".
                             "\$config['db']['database'] = \"$sqlUrl\";\n\n");
    return $res;
}

function runDbScript($filename, $conn)
{
    $sql = trim(file_get_contents($filename));
    $stmts = explode(';', $sql);
    foreach ($stmts as $stmt) {
        $stmt = trim($stmt);
        if (!mb_strlen($stmt)) {
            continue;
        }
        $res = mysql_query($stmt, $conn);
        if ($res === false) {
            return $res;
        }
    }
    return true;
}

?>
<html>
<head>
	<title>Install Laconica</title>
	<link rel="stylesheet" type="text/css" href="theme/base/css/display.css?version=0.7.1" media="screen, projection, tv"/>
	<link rel="stylesheet" type="text/css" href="theme/base/css/modal.css?version=0.7.1" media="screen, projection, tv"/>
	<link rel="stylesheet" type="text/css" href="theme/default/css/display.css?version=0.7.1" media="screen, projection, tv"/>
</head>
<body>
	<div id="wrap">
	<div id="core">
	<div id="content">
	<h1>Install Laconica</h1>
<? main() ?>
	</div>
	</div>
	</div>
</body>
</html>