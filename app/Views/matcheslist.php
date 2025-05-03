<!DOCTYPE html>
<html>
<body>

<h2>Player6</h2>

<form action="updatematchtime" method="post">
  <label for="fname">Match id:</label><br>

  <select name="matchId" id="matchId" required>
  	<?php if (count($list) > 0) { ?>
  		<?php foreach ($list as $key => $value) { ?>
  			<option value="<?php echo $value['matchId']; ?>"><?php echo $value['title']; ?></option>
  		<?php	} ?>
  	<?php } ?>
	</select>

  <label for="lname">Start Time (Only UTC Time):</label><br>
  <input type="datetime-local" id="startTime" name="startTime" value="" required />
  <input type="submit" value="Submit">

</form> 

</body>
</html>

