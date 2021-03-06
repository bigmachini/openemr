<?php
/**
 * This report lists patients that were seen within a given date
 * range, or all patients if no date range is entered.
 *
 * Copyright (C) 2006-2016 Rod Roark <rod@sunsetsystems.com>
 * Copyright (C) 2017 Brady Miller <brady.g.miller@gmail.com>
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * @package OpenEMR
 * @author  Rod Roark <rod@sunsetsystems.com>
 * @author  Brady Miller <brady.g.miller@gmail.com>
 * @link    http://www.open-emr.org
 */


 require_once("../globals.php");
 require_once("$srcdir/patient.inc");
 require_once("$srcdir/options.inc.php");

// Prepare a string for CSV export.
function qescape($str) {
  $str = str_replace('\\', '\\\\', $str);
  return str_replace('"', '\\"', $str);
}

 $from_date = DateToYYYYMMDD($_POST['form_from_date']);
 $to_date   = DateToYYYYMMDD($_POST['form_to_date']);
 if (empty($to_date) && !empty($from_date)) $to_date = date('Y-12-31');
 if (empty($from_date) && !empty($to_date)) $from_date = date('Y-01-01');

$form_provider = empty($_POST['form_provider']) ? 0 : intval($_POST['form_provider']);

// In the case of CSV export only, a download will be forced.
if ($_POST['form_csvexport']) {
  header("Pragma: public");
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Content-Type: application/force-download");
  header("Content-Disposition: attachment; filename=patient_list.csv");
  header("Content-Description: File Transfer");
}
else {
?>
<html>
<head>
<?php html_header_show();?>
<title><?php xl('Patient List','e'); ?></title>

<?php $include_standard_style_js = array("datetimepicker","report_helper.js"); ?>
<?php require "{$GLOBALS['srcdir']}/templates/standard_header_template.php"; ?>

<script language="JavaScript">
 var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';

$(document).ready(function() {
  oeFixedHeaderSetup(document.getElementById('mymaintable'));
  top.printLogSetup(document.getElementById('printbutton'));

  $('.datepicker').datetimepicker({
   <?php $datetimepicker_timepicker = false; ?>
   <?php $datetimepicker_showseconds = false; ?>
   <?php $datetimepicker_formatInput = true; ?>
   <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
   <?php // can add any additional javascript settings to datetimepicker here; need to prepend first setting with a comma ?>
  });

});

</script>

<style type="text/css">

/* specifically include & exclude from printing */
@media print {
    #report_parameters {
        visibility: hidden;
        display: none;
    }
    #report_parameters_daterange {
        visibility: visible;
        display: inline;
		margin-bottom: 10px;
    }
    #report_results table {
       margin-top: 0px;
    }
}

/* specifically exclude some from the screen */
@media screen {
	#report_parameters_daterange {
		visibility: hidden;
		display: none;
	}
	#report_results {
		width: 100%;
	}
}

</style>

</head>

<body class="body_top">

<!-- Required for the popup date selectors -->
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>

<span class='title'><?php xl('Report','e'); ?> - <?php xl('Patient List','e'); ?></span>

<div id="report_parameters_daterange">
<?php if (!(empty($to_date) && empty($from_date))) { ?>
  <?php echo date("d F Y", strtotime($from_date)) ." &nbsp; to &nbsp; ". date("d F Y", strtotime($to_date)); ?>
<?php } ?>
</div>

<form name='theform' id='theform' method='post' action='patient_list.php' onsubmit='return top.restoreSession()'>

<div id="report_parameters">

<input type='hidden' name='form_refresh' id='form_refresh' value=''/>
<input type='hidden' name='form_csvexport' id='form_csvexport' value=''/>

<table>
 <tr>
  <td width='60%'>
	<div style='float:left'>

	<table class='text'>
		<tr>
      <td class='control-label'>
        <?php xl('Provider','e'); ?>:
      </td>
      <td>
	      <?php
         generate_form_field(array('data_type' => 10, 'field_id' => 'provider',
           'empty_title' => '-- All --'), $_POST['form_provider']);
	      ?>
      </td>
			<td class='control-label'>
			   <?php xl('Visits From','e'); ?>:
			</td>
			<td>
			   <input class='datepicker form-control' type='text' name='form_from_date' id="form_from_date" size='10' value='<?php echo oeFormatShortDate($from_date) ?>'>
			</td>
			<td class='control-label'>
			   <?php xl('To','e'); ?>:
			</td>
			<td>
			   <input class='datepicker form-control' type='text' name='form_to_date' id="form_to_date" size='10' value='<?php echo oeFormatShortDate($to_date) ?>'>
			</td>
		</tr>
	</table>

	</div>

  </td>
  <td align='left' valign='middle' height="100%">
	<table style='border-left:1px solid; width:100%; height:100%' >
		<tr>
			<td>
        <div class="text-center">
				  <div class="btn-group" role="group">
	  				<a href='#' class='btn btn-default btn-save' onclick='$("#form_csvexport").val(""); $("#form_refresh").attr("value","true"); $("#theform").submit();'>
	  				  <?php echo xlt('Submit'); ?>
	  				</a>
	  				<a href='#' class='btn btn-default btn-transmit' onclick='$("#form_csvexport").attr("value","true"); $("#theform").submit();'>
	  					<?php echo xlt('Export to CSV'); ?>
	  				</a>
	  				<?php if ($_POST['form_refresh']) { ?>
	  				  <a href='#' id='printbutton' class='btn btn-default btn-print'>
	  						<?php xl('Print','e'); ?>
  					  </a>
  					<?php } ?>
  			  </div>
        </div>
			</td>
		</tr>
	</table>
  </td>
 </tr>
</table>
</div> <!-- end of parameters -->

<?php
} // end not form_csvexport

if ($_POST['form_refresh'] || $_POST['form_csvexport']) {
  if ($_POST['form_csvexport']) {
    // CSV headers:
    echo '"' . xl('Last Visit') . '",';
    echo '"' . xl('First') . '",';
    echo '"' . xl('Last') . '",';
    echo '"' . xl('Middle') . '",';
    echo '"' . xl('ID') . '",';
    echo '"' . xl('Street') . '",';
    echo '"' . xl('City') . '",';
    echo '"' . xl('State') . '",';
    echo '"' . xl('Zip') . '",';
    echo '"' . xl('Home Phone') . '",';
    echo '"' . xl('Work Phone') . '"' . "\n";
  }
  else {
?>

<div id="report_results">
<table id='mymaintable'>
 <thead>
  <th> <?php xl('Last Visit','e'); ?> </th>
  <th> <?php xl('Patient','e'); ?> </th>
  <th> <?php xl('ID','e'); ?> </th>
  <th> <?php xl('Street','e'); ?> </th>
  <th> <?php xl('City','e'); ?> </th>
  <th> <?php xl('State','e'); ?> </th>
  <th> <?php xl('Zip','e'); ?> </th>
  <th> <?php xl('Home Phone','e'); ?> </th>
  <th> <?php xl('Work Phone','e'); ?> </th>
 </thead>
 <tbody>
<?php
  } // end not export
  $totalpts = 0;
  $query = "SELECT " .
   "p.fname, p.mname, p.lname, p.street, p.city, p.state, " .
   "p.postal_code, p.phone_home, p.phone_biz, p.pid, p.pubpid, " .
   "count(e.date) AS ecount, max(e.date) AS edate, " .
   "i1.date AS idate1, i2.date AS idate2, " .
   "c1.name AS cname1, c2.name AS cname2 " .
   "FROM patient_data AS p ";
  if (!empty($from_date)) {
   $query .= "JOIN form_encounter AS e ON " .
   "e.pid = p.pid AND " .
   "e.date >= '$from_date 00:00:00' AND " .
   "e.date <= '$to_date 23:59:59' ";
   if ($form_provider) {
    $query .= "AND e.provider_id = '$form_provider' ";
   }
  }
  else {
   if ($form_provider) {
    $query .= "JOIN form_encounter AS e ON " .
    "e.pid = p.pid AND e.provider_id = '$form_provider' ";
   }
   else {
    $query .= "LEFT OUTER JOIN form_encounter AS e ON " .
    "e.pid = p.pid ";
   }
  }
  $query .=
   "LEFT OUTER JOIN insurance_data AS i1 ON " .
   "i1.pid = p.pid AND i1.type = 'primary' " .
   "LEFT OUTER JOIN insurance_companies AS c1 ON " .
   "c1.id = i1.provider " .
   "LEFT OUTER JOIN insurance_data AS i2 ON " .
   "i2.pid = p.pid AND i2.type = 'secondary' " .
   "LEFT OUTER JOIN insurance_companies AS c2 ON " .
   "c2.id = i2.provider " .
   "GROUP BY p.lname, p.fname, p.mname, p.pid, i1.date, i2.date " .
   "ORDER BY p.lname, p.fname, p.mname, p.pid, i1.date DESC, i2.date DESC";
  $res = sqlStatement($query);

  $prevpid = 0;
  while ($row = sqlFetchArray($res)) {
   if ($row['pid'] == $prevpid) continue;
   $prevpid = $row['pid'];
   $age = '';
   if ($row['DOB']) {
    $dob = $row['DOB'];
    $tdy = $row['edate'] ? $row['edate'] : date('Y-m-d');
    $ageInMonths = (substr($tdy,0,4)*12) + substr($tdy,5,2) -
                   (substr($dob,0,4)*12) - substr($dob,5,2);
    $dayDiff = substr($tdy,8,2) - substr($dob,8,2);
    if ($dayDiff < 0) --$ageInMonths;
    $age = intval($ageInMonths/12);
   }

   if ($_POST['form_csvexport']) {
    echo '"' . oeFormatShortDate(substr($row['edate'], 0, 10)) . '",';
    echo '"' . qescape($row['lname']) . '",';
    echo '"' . qescape($row['fname']) . '",';
    echo '"' . qescape($row['mname']) . '",';
    echo '"' . qescape($row['pubpid']) . '",';
    echo '"' . qescape($row['street']) . '",';
    echo '"' . qescape($row['city']) . '",';
    echo '"' . qescape($row['state']) . '",';
    echo '"' . qescape($row['postal_code']) . '",';
    echo '"' . qescape($row['phone_home']) . '",';
    echo '"' . qescape($row['phone_biz']) . '"' . "\n";
   }
   else {
?>
 <tr>
  <td>
   <?php echo oeFormatShortDate(substr($row['edate'], 0, 10)) ?>
  </td>
  <td>
    <?php echo htmlspecialchars( $row['lname'] . ', ' . $row['fname'] . ' ' . $row['mname'] ) ?>
  </td>
  <td>
   <?php echo $row['pubpid'] ?>
  </td>
  <td>
   <?php echo $row['street'] ?>
  </td>
  <td>
   <?php echo $row['city'] ?>
  </td>
  <td>
   <?php echo $row['state'] ?>
  </td>
  <td>
   <?php echo $row['postal_code'] ?>
  </td>
  <td>
   <?php echo $row['phone_home'] ?>
  </td>
  <td>
   <?php echo $row['phone_biz'] ?>
  </td>
 </tr>
<?php
   } // end not export
   ++$totalpts;
  } // end while
  if (!$_POST['form_csvexport']) {
?>

 <tr class="report_totals">
  <td colspan='9'>
   <?php xl('Total Number of Patients','e'); ?>
   :
   <?php echo $totalpts ?>
  </td>
 </tr>

</tbody>
</table>
</div> <!-- end of results -->
<?php
  } // end not export
} // end if refresh or export

if (!$_POST['form_refresh'] && !$_POST['form_csvexport']) {
?>
<div class='text'>
 	<?php echo xl('Please input search criteria above, and click Submit to view results.', 'e' ); ?>
</div>
<?php
}

if (!$_POST['form_csvexport']) {
?>

</form>
</body>

</html>
<?php
} // end not export
?>
