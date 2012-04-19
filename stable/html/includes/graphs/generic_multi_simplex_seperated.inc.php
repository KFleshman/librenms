<?php

include("includes/graphs/common.inc.php");

if($width > "500")
{
  $descr_len=24;
} else {
  $descr_len=12;
  $descr_len += round(($width - 250) / 8);
}

if ($nototal) { $descrlen += "2"; $unitlen += "2";}
$unit_text = str_pad(truncate($unit_text,$unitlen),$unitlen);

if($width > "500")
{
  $rrd_options .= " COMMENT:'".substr(str_pad($unit_text, $descr_len+5),0,$descr_len+5)."Now      Min      Max     Avg\l'";
  if (!$nototal) { $rrd_options .= " COMMENT:'Total      '"; }
  $rrd_options .= " COMMENT:'\l'";
} else {
  $rrd_options .= " COMMENT:'".substr(str_pad($unit_text, $descr_len+5),0,$descr_len+5)."Now      Min      Max     Avg\l'";

}

$unitlen  = "10";
if ($nototal) { $descrlen += "2"; $unitlen += "2";}
$unit_text = str_pad(truncate($unit_text,$unitlen),$unitlen);

$colour_iter=0;
foreach ($rrd_list as $i => $rrd)
{
  if ($rrd['colour'])
  {
    $colour = $rrd['colour'];
  } else {
    if (!$config['graph_colours'][$colours][$colour_iter]) { $colour_iter = 0; }
    $colour = $config['graph_colours'][$colours][$colour_iter];
    $colour_iter++;
  }

  $descr     = str_replace(":", "\:", substr(str_pad($rrd['descr'], $descr_len),0,$descr_len));

  $rrd_options .= " DEF:".$rrd['ds'].$i."=".$rrd['filename'].":".$rrd['ds'].":AVERAGE ";

  if ($simple_rrd)
  {
    $rrd_options .= " CDEF:".$rrd['ds'].$i."min=".$rrd['ds'].$i." ";
    $rrd_options .= " CDEF:".$rrd['ds'].$i."max=".$rrd['ds'].$i." ";
  } else {
    $rrd_options .= " DEF:".$rrd['ds'].$i."min=".$rrd['filename'].":".$rrd['ds'].":MIN ";
    $rrd_options .= " DEF:".$rrd['ds'].$i."max=".$rrd['filename'].":".$rrd['ds'].":MAX ";
  }

  ## Suppress totalling?
  if (!$nototal)
  {
    $rrd_options .= " VDEF:tot".$rrd['ds'].$i."=".$rrd['ds'].$i.",TOTAL";
  }

  ## This this not the first entry?
  if ($i) { $stack="STACK"; }

  # if we've been passed a multiplier we must make a CDEF based on it!
  $g_defname = $rrd['ds'];
  if (is_numeric($multiplier))
  {
    $g_defname = $rrd['ds'] . "_cdef";
    $rrd_options .= " CDEF:" . $g_defname . $i . "=" . $rrd['ds'] . $i . "," . $multiplier . ",*";
    $rrd_options .= " CDEF:" . $g_defname . $i . "min=" . $rrd['ds'] . $i . "min," . $multiplier . ",*";
    $rrd_options .= " CDEF:" . $g_defname . $i . "max=" . $rrd['ds'] . $i . "max," . $multiplier . ",*";

  ## If we've been passed a divider (divisor!) we make a CDEF for it.
  } elseif (is_numeric($divider))
  {
    $g_defname = $rrd['ds'] . "_cdef";
    $rrd_options .= " CDEF:" . $g_defname . $i . "=" . $rrd['ds'] . $i . "," . $divider . ",/";
    $rrd_options .= " CDEF:" . $g_defname . $i . "min=" . $rrd['ds'] . $i . "min," . $divider . ",/";
    $rrd_options .= " CDEF:" . $g_defname . $i . "max=" . $rrd['ds'] . $i . "max," . $divider . ",/";
  }

  ## Are our text values related to te multiplier/divisor or not?
  if (isset($text_orig) && $text_orig)
  {
    $t_defname = $rrd['ds'];
  } else {
    $t_defname = $g_defname;
  }

  $rrd_options .= " AREA:".$g_defname.$i."#".$colour.":'".$descr."':$stack";

  $rrd_options .= " GPRINT:".$t_defname.$i.":LAST:%5.2lf%s GPRINT:".$t_defname.$i."min:MIN:%5.2lf%s";
  $rrd_options .= " GPRINT:".$t_defname.$i."max:MAX:%5.2lf%s GPRINT:".$t_defname.$i.":AVERAGE:'%5.2lf%s\\n'";

  if (!$nototal) { $rrd_options .= " GPRINT:tot".$rrd['ds'].$i.":%6.2lf%s".str_replace("%", "%%", $total_units).""; }

  $rrd_options .= " COMMENT:'\\n'";
}

?>