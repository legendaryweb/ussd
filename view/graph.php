<?php
require_once ($_SERVER["DOCUMENT_ROOT"] . "/ussd/lib/jpgraph/jpgraph.php");
require_once ($_SERVER["DOCUMENT_ROOT"] . "/ussd/lib/jpgraph/jpgraph_bar.php");
//require_once ($_SERVER["DOCUMENT_ROOT"] . "/ussd/lib/jpgraph/jpgraph_date.php");
include_once ($_SERVER["DOCUMENT_ROOT"] . "/ussd/view/settings.php");

$sql =  <<<SQL
SELECT update_date AS up_d, create_date AS cr_d FROM users 
WHERE 
update_date >= date_sub(now(), interval 7 month)
OR
create_date >= date_sub(now(), interval 7 month) 
SQL;

$get_data = $con->query($sql);
$data = $get_data->fetchAll(PDO::FETCH_ASSOC);

//Period Function
//Tests if a period fits in a given month numeric value based on now
function period($t_date, $month) {
    
$first_day_of_month = date("Y-m-01 00:00:00", (strtotime("-{$month} month")));
$last_day_of_month = date("Y-m-t 23:59:59", (strtotime("-{$month} month")));

if (($t_date >= $first_day_of_month) && ($t_date <= $last_day_of_month)){
    return true;
} else {
    return false;
}
}

//Compile values
$update = array(0,0,0,0,0,0);
$create = array(0,0,0,0,0,0);
foreach ($data as $v) {
    for ($i=0;$i <= 6; $i++) {
        if (isset($v['up_d'])) {
            if (period($v['up_d'], $i)) {
                $update[$i]++;
            }
        } else if (period($v['cr_d'], $i)) {
            $create[$i]++;
        }
    }
}

$data1y=array_reverse($update);
$data2y=array_reverse($create);

// Create the graph. These two calls are always required
$graph = new Graph(710, 400);
$graph->SetScale("textlin");
$graph->graph_theme = null;
$graph->img->SetMargin(40, 30, 20, 40);

$months = $gDateLocale->GetShortMonth();
$datem = intval(date('n'))-1;
for ($i = $datem;$i >= ($datem-5); $i--) {
    $z = ($i<0) ? (12+$i) : $i;
    $mon[$i] = $months[$z];
}

$graph->xaxis->SetTickLabels(array_reverse($mon));

// Create the bar plots
$b1plot = new BarPlot($data1y);
$b1plot->SetFillColor("green");

$b2plot = new BarPlot($data2y);
$b2plot->SetFillColor("blue");

// Create the grouped bar plot
$abplot = new AccBarPlot(array($b1plot,$b2plot));

$gbarplot = new GroupBarPlot(array($b1plot,$b2plot));
//$gbarplot->value->Show();
$gbarplot->SetWidth(0.6);
$graph->Add($gbarplot);


$graph->title->Set("Total signups(blue) and updates(green) monthly");
$graph->xaxis->title->Set("per month");
$graph->yaxis->title->Set("Total Users");

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);


// Send back the HTML page
$graph->Stroke();
?>