<?php

$servername = "localhost"; # server name
$username = "root";
$password = "";
$database = "gld";

// $info -> 0 is a purchase history, 1 is the item info
// $remind -> 1 is almost out, quantity < remindat

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

# Truncates $str so that it has $maxChar max characters followed by '...' and returns it
function truncate($str, $maxChar) {
    if (!$str) {
        return '';
    }
    if (strlen($str) > $maxChar) {
        return substr($str, 0, $maxChar) . "...";
    } else {
        return $str;
    }
}

$info = array();
$curTable = "inv";
$sql = "SELECT * FROM inv order by (quantity <= remindat) desc, quantity";
if ($_POST) {
    foreach($_POST as $key => $value) {
        if (strpos($key, 'quantity') !== false) {
            $parts = explode('_', $key);
            $id = $parts[1];
            $old_quantity = $parts[2];
            $quantity = $value;
        } else if (strpos($key, 'remove') !== false) {
            $parts = explode('_', $key);
            $id = $parts[1];
            $edit = '-';
        } else if (strpos($key, 'add') !== false) {
            $parts = explode('_', $key);
            $id = $parts[1];
            $edit = '+';
        }
    }
    if (isset($id)) {
        if ($edit === '+') {
            $quantity += $old_quantity;
            $status = "+ QTY";
        } else {
            $quantity = $old_quantity - $quantity;
            $status = "- QTY";
        }
        $sql = "UPDATE inv SET quantity = $quantity WHERE id = $id";
        $result = $conn->query($sql);

        $sql = "SELECT * FROM inv WHERE id = $id";
        $result = $conn->query($sql);
        if ($result === false) {
            echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
        } elseif ($result->num_rows > 0) {
            $info = $result->fetch_assoc();
        }
        
        $item = $info['item'];
        $brand = $info['brand'];
        $supply = $info['supply'];
        $remindat = $info['remindat'];
        $price = $info['price'];
        $description = $info['description'];
        $note = $info['note'];
        $currentDatetime = date("Y-m-d H:i:s");
        $sql = "INSERT INTO history (date, item, quantity, brand, supply, remindat, price, description, note, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissidsss", $currentDatetime, $item, $quantity, $brand, $supply, $remindat, $price, $description, $note, $status);
        $stmt->execute();
        $sql = "SELECT * FROM inv order by (quantity <= remindat) desc, quantity";
    }
	if (isset($_POST['itembtn'])) {
		$item = $_POST['itemSelect'] ?? 'None';
		$sql = "SELECT * FROM inv";
		if ($item != '') {
			$sql = $sql . " WHERE item = '$item'";
		}
        $sql = $sql . " ORDER BY (quantity <= remindat) desc, quantity";
	} elseif (isset($_POST['historybtn'])) {
		$item = $_POST['itemSelect'] ?? '';
		$sql = "SELECT * FROM history";
		if ($item != '') {
			$sql = $sql . " WHERE item = '" . $item . "'";
		}
        $sql = $sql . " ORDER BY date DESC";
		$curTable = "history";
	} elseif (isset($_POST['clear'])) {
        $_POST['itemSelect'] = '';
    } elseif (isset($_POST['insertbtn'])) {
        $item = ($_POST['item'] ?? 'None');
        if ($item === '') {
            $item = 'None';
        }
        $quantity = 0;
        $brand = ($_POST['brand'] ?? '');
        $supply = ($_POST['supply'] ?? '');
        $price = ($_POST['price'] ?? '');
        $description = ($_POST['description'] ?? '');
        
        // Insert into item list
        $sql = "INSERT INTO inv (item, quantity, brand, supply, price, description) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissds", $item, $quantity, $brand, $supply, $price, $description);
        $stmt->execute();

        $currentDatetime = date("Y-m-d H:i:s");
        $remindat = '0';
        $note = '';
        $status = "New";
        $sql = "INSERT INTO history (date, item, quantity, brand, supply, remindat, price, description, note, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissidsss", $currentDatetime, $item, $quantity, $brand, $supply, $remindat, $price, $description, $note, $status);
        $stmt->execute();
        $sql = "SELECT * FROM inv order by (quantity <= remindat) desc, quantity";
	}
}

$info = array();
$result = $conn->query($sql);
if ($result === false) {
	echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
} elseif ($result->num_rows > 0) {
	while ($row = $result->fetch_assoc()) {
        array_push($info, $row);
    }
}

?>

<html>
<head>
<link rel="stylesheet" type="text/css" href="invstyles.css">
<title>INV</title>

<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="/resources/demos/style.css">
<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
  <script>
  $( function() {
    $( ".datepicker" ).datepicker();
  } );
  </script>
	<script>
	function myFunction() {
		alert("I am an alert box!"); // this is the message in ""
	}
    function openPrintPage() {
        window.open("print.php", "_blank"); // Open print.php in a new tab/window
    }
	</script>
</head>
<body>
    <div class = "centered-container">
<br>
<h2 class="hide-on-print">INVENTORY</h2>
<br>

<!--SELECTION TABLE -->
<table border="1" class="hide-on-print">
<tr>
    <td style="background-color: #e9ebf4;">
<form name="searchForm" method="POST">
   <b> ITEM:</b> <input name="itemSelect" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['itemSelect']) ? $_POST['itemSelect'] : '' ?>">
	<input type="submit" name="itembtn" value="ITEMS" id="itemgo" />
	<input type="submit" name="historybtn" value="HISTORY" id="historygo" />
	<input type="submit" name="clear" value="CLEAR" id="cleargo" />
	<input type="button" name="print" style="background-color: #42f" value="PRINT" onclick="openPrintPage()" />
  <br>
</form>
</td>
</tr>
</table>

<!-- NEW ITEM TABLE -->
<table border="1" class="hide-on-print">
<tr>
    <td style="background-color: #e9ebf4;">
<form name="inputForm" method="POST">
   <b> ITEM:</b> <input name="item" type="text" style="height:25pt;width:100pt;">
   <b> BRAND:	</b> <input name="brand" type="text" style="height:25pt;width:100pt;">
   <b> SUPPLY:	</b> <input name="supply" type="text" style="height:25pt;width:100pt;">
   <b> PRICE:</b> <input class="price" name="price" type="number" step='0.01' style="height:25pt;width:100pt;">
   <b> DESCRIPTION:	</b> <input name="description" type="text" style="height:25pt;width:100pt;">
   <input type="submit" name="insertbtn" value="INSERT NEW ITEM" id="insertgo" />
	<br>
</form>
</td>
</tr>
</table>


    <?php if (isset($_POST['historybtn'])) { ?>

 <table border="1">
    <tr style="background-color: #eee;">
      
	  <th>DATE</th>
      <th>ITEM</th>
	  <th>QUANTITY</th>
	  <th>REMIND @</th>
	  <th>BRAND</th>
	  <th>SUPPLY</th>
	  <th>PRICE</th>
	  <th>DESCRIPTION</th>
      <th>NOTE</th>
      <th>STATUS</th>
    </tr>
	
	<?php
	// Set the background color of the table
	
	$colour = "#c3cde6";
	foreach ($info as $row){
        $row['description'] = truncate($row['description'], 50);
        $row['note'] = truncate($row['note'], 50);
	?>
	<tr bgcolor="<?= $colour ?>">
        <td class="center"><?= $row['date'] ?></td>
        <td class="center"><a href="view_item.php?id=<?= $row['id'] ?>" target="_blank"><?= $row['item'] ?></a></td>
        <td class="center"><?= $row['quantity'] ?></td>
        <td class="center"><?= $row['remindat'] ?></td>
        <td class="center"><?= $row['brand'] ?></td>
        <td class="center"><?= $row['supply'] ?></td>
        <td class="center"><?= $row['price'] ?></td>
        <td class="center"><?= $row['description'] ?></td>
        <td class="center"><?= $row['note'] ?></td>
        <td class="center"><?= $row['status'] ?></td>
	</tr>
	<?php
	}
	
	
	?>
</table>
	<?php } else { ?>
		<table border="1">
    <tr style="background-color: #eee;">
      
      <th>ITEM</th>
	  <th>QUANTITY</th>
	  <th>BRAND</th>
	  <th>SUPPLY</th>
	  <th>PRICE</th>
	  <th>DESCRIPTION</th>
      <th>NOTE</th>
      <th class="hide-on-print">+/-</th>
    </tr>
	
	<?php
	// Set the background color of the table
	
	$colour = "#c3cde6";
	foreach ($info as $row){
		if ($row['quantity'] <= $row['remindat']) { // Needs stock
			$colour = "#ff9999";
		} else {
			$colour = "#c3cde6";
		}
        $row['description'] = truncate($row['description'], 50);
        $row['note'] = truncate($row['note'], 50);
	?>
	<tr bgcolor="<?= $colour ?>">
        <td class="center"><a href="view_item.php?id=<?= $row['id'] ?>" target="_blank"><?= $row['item'] ?></a></td>
        <td class="center"><?= $row['quantity'] ?></td>
        <td class="center"><?= $row['brand'] ?></td>
        <td class="center"><?= $row['supply'] ?></td>
        <td class="center"><?= $row['price'] ?></td>
        <td class="center"><?= $row['description'] ?></td>
        <td class="center"><?= $row['note'] ?></td>
        <td class="center hide-on-print">
            <form method='POST'>
                <input type="submit" name="<?php echo "remove_" . $row['id'];?>" value="-"/>
                <input name="<?php echo "quantity_" . $row['id'] . '_' . $row['quantity'];?>" type="number" style="height:25pt;width:40pt;">
                <input type="submit" name="<?php echo "add_" . $row['id'];?>" value="+" id="add"/>
            </form>
        </td>
	</tr>
	<?php
	}
	
	
	?>
</table>
	<?php } ?>
</div>