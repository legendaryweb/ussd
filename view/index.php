<?php

//Database connection
$con = new PDO("mysql:host=localhost;dbname=ussd_signups", "root", "9139");
$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$get_users = $con->query("SELECT * FROM users");
$users = $get_users->fetchAll(PDO::FETCH_ASSOC);
       
?>
<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<title>View Users</title>

<script src="js/jquery-2.2.3.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<link href="css/style.css" rel="stylesheet" type="text/css">
<link href="css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">
<script type="text/javascript">
$(document).ready(function() {
    $('#datatable').dataTable({
        "order": [ 6, "desc" ],
         "iDisplayLength": 25,
        "aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });
});
</script>
</head>
<body>
    <table id="datatable">
      <thead>
          <tr> 
            <th width="20"><b>ID</b></th>
            <th width="100"><b>Name</b></th>
            <th width="100"><b>Surname</b></th>
            <th width="120"><b>Cell Number</b></th>
            <th width="80"><b>Network</b></th>
            <th width="110"><b>Update Date</b></th>
            <th width="110"><b>Created Date</b></th>
          </tr>
      </thead>
      <tbody>
      <?php 
            foreach ($users as $data) {
      ?>
          <tr>
            <td><?=$data['id']?></td>
            <td><?=$data['name']?></td>
            <td><?=$data['surname']?></td>
            <td><?=$data['cell_number']?></td>
            <td><?=$data['network']?></td>
            <td><?=$data['update_date']?></td>
            <td><?=$data['create_date']?></td>
          </tr>
       
      <?php
            }
      ?>

      </tbody>        
        <tr>
            <td colspan='7' align='right' style="background-color: #3782ff">
            <a id="link_graph" href="http://37.139.2.124/ussd/view/graph.php">Show Graph</a>
            </td>
        </tr>
      </table>
</body>
</html>