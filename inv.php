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


$curTable = "inv";
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
            exit();
            $parts = explode('_', $key);
            $id = $parts[1];
            $edit = '+';
        }
    }
    if (isset($id)) {
        exit();
        if ($edit === '+') {
            $quantity += $old_quantity;
        } else {
            $quantity = $old_quantity - $quantity;
        }
        $sql = "UPDATE inv SET quantity = $quantity WHERE id = $id";
        $result = $conn->query($sql);

    }
	if (isset($_POST['itembtn'])) {
		$item = $_POST['item'] ?? '';
		$sql = "SELECT * FROM inv ";
		if ($item != '') {
			$sql = $sql . "AND item = '" . $item . "'";
		}
        $sql = $sql . " ORDER BY (quantity <= remindat) desc, quantity";
	} elseif (isset($_POST['historybtn'])) {
		$item = $_POST['item'] ?? '';
		$sql = "SELECT * FROM history ";
		if ($item != '') {
			$sql = $sql . "AND item = '" . $item . "'";
		}
        $sql = $sql . " ORDER BY date DESC";
		$curTable = "history";
	} elseif (isset($_POST['insertbtn'])) {
        $item = ($_POST['item'] ?? '');
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
} else {
    $sql = "SELECT * FROM inv order by (quantity <= remindat) desc, quantity";
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
<title>INV</title>
<style>
    body, h1, h2, h3, p, ul, li {
    margin: 0;
    padding: 0;
    }
    body {
        font-family: Arial, sans-serif;
        background-color: #f7f7f7;
        color: #333;
        width: 90%;
    }
    .container {
    max-width: 1260px;
    margin: 0 auto;
    padding: 20px;
    }

    .centered-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
    table {
    border-collapse: collapse;
    width: 1560px;
    border: 2px solid black;
    margin-top: 20px;
}
th, td {
    border: 1px solid #333;
    padding: 10px;
    text-align: center;
}
  input[type="submit"] {
    padding: 8px 15px;
    font-size: 14px;
    border: none;
    background-color: #333;
    color: #fff;
    cursor: pointer;
    transition: background-color 0.2s, color 0.2s;
}
input[type='text'], .price {
    margin-right: 20px;
}

input[type="submit"]:hover,
input[type="text"]:focus {
    background-color: white; /* Change background color */
    color: black; /* Change text color */
    outline: none;
}

.center { text-align: center; 
}
  ul#newlist {
    list-style: none;
    margin: 0;
    padding: 0;
}

ul#newlist li {
    padding: 5px 0;
    border-bottom: 1px solid #ccc;
}
</style>

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
	</script>
</head>
<body>
    <div class = "centered container">
<br>
<h2>INV</h2>
<br>

<!--SELECTION TABLE -->
<table border="1">
<tr>
    <td style="background-color: #e9ebf4;">
<form name="searchForm" method="POST">
	
   
   <b> Item:</b> <input name="item" type="text" style="height:25pt;width:100pt;">
	<input type="submit" name="itembtn" value="Items" id="itemgo" />
	<input type="submit" name="historybtn" value="History" id="historygo" />
  <br>
</form>
</td>
</tr>
</table>


<!-- NEW ITEM TABLE -->
<table border="1">
<tr>
    <td style="background-color: #e9ebf4;">
<form name="inputForm" method="POST">
   <b> Item:</b> <input name="item" type="text" style="height:25pt;width:100pt;">
   <b> Brand:	</b> <input name="brand" type="text" style="height:25pt;width:100pt;">
   <b> Supply:	</b> <input name="supply" type="text" style="height:25pt;width:100pt;">
   <b> Price:</b> <input class="price" name="price" type="number" step='0.01' style="height:25pt;width:100pt;">
   <b> Description:	</b> <input name="description" type="text" style="height:25pt;width:100pt;">
   <input type="submit" name="insertbtn" value="Insert new item" id="insertgo" />
	<br>
</form>
</td>
</tr>
</table>


    <?php if (isset($_POST['historybtn'])) { ?>

 <table border="1">
    <tr style="background-color: #eee;">
      
	  <th>Date</th>
      <th>Item</th>
	  <th>Quantity</th>
	  <th>Remind @</th>
	  <th>Brand</th>
	  <th>Supply</th>
	  <th>Price</th>
	  <th>Description</th>
      <th>Note</th>
      <th>Status</th>
    </tr>
	
	<?php
	// Set the background color of the table
	
	$colour = "#c3cde6";
	foreach ($info as $row){
        $encoded_url = $row['item'] . "/" . $row['brand'] . "/" . $row['supply'];
		
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
      
      <th>Item</th>
	  <th>Quantity</th>
	  <th>Brand</th>
	  <th>Supply</th>
	  <th>Price</th>
	  <th>Description</th>
      <th>Note</th>
      <th>+/-</th>
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
	?>
	<tr bgcolor="<?= $colour ?>">
        <td class="center"><a href="view_item.php?id=<?= $row['id'] ?>" target="_blank"><?= $row['item'] ?></a></td>
        <td class="center"><?= $row['quantity'] ?></td>
        <td class="center"><?= $row['brand'] ?></td>
        <td class="center"><?= $row['supply'] ?></td>
        <td class="center"><?= $row['price'] ?></td>
        <td class="center"><?= $row['description'] ?></td>
        <td class="center"><?= $row['note'] ?></td>
        <td class="center">
            <form method='POST'>
                <input type="submit" name="<?php "remove_" . $row['id']?>" value="-"/>
                <input name="<?php "quantity_" . $row['id'] . '_' . $row['quantity']?>" type="number" style="height:25pt;width:40pt;">
                <input type="submit" name="<?php "add_" . $row['id']?>" value="+" id="add"/>
            </form>
        </td>
	</tr>
	<?php
	}
	
	
	?>
</table>
	<?php } ?>
</div>