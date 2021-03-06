<?php
chdir('..');
include 'common.inc';
require_once('./benchmarks/data.inc.php');
$page_keywords = array('Benchmarks','Webpagetest','Website Speed Test','Page Speed');
$page_description = "WebPagetest benchmarks";
$aggregate = 'median';
if (array_key_exists('aggregate', $_REQUEST))
    $aggregate = $_REQUEST['aggregate'];
$benchmarks = GetBenchmarks();

if (array_key_exists('f', $_REQUEST)) {
    $out_data = array();
} else {
  $INCLUDE_ERROR_BARS = true;
?>
<!DOCTYPE html>
<html>
    <head>
        <title>WebPagetest - Benchmarks</title>
        <meta http-equiv="charset" content="iso-8859-1">
        <meta name="keywords" content="Performance, Optimization, Pagetest, Page Design, performance site web, internet performance, website performance, web applications testing, web application performance, Internet Tools, Web Development, Open Source, http viewer, debugger, http sniffer, ssl, monitor, http header, http header viewer">
        <meta name="description" content="Speed up the performance of your web pages with an automated analysis">
        <meta name="author" content="Patrick Meenan">
        <?php $gaTemplate = 'About'; include ('head.inc'); ?>
        <script type="text/javascript" src="/js/dygraph-combined.js?v=1.0.1"></script>
        <style type="text/css">
        .chart-container { clear: both; width: 875px; height: 350px; margin-left: auto; margin-right: auto; padding: 0;}
        .benchmark-chart { float: left; width: 700px; height: 350px; }
        .benchmark-legend { float: right; width: 150px; height: 350px; }
        </style>
    </head>
    <body>
        <div class="page">
            <?php
            $tab = 'Benchmarks';
            include 'header.inc';
            ?>
            
            <script type="text/javascript">
            var compareTo = undefined;
            <?php
            $bmData = array();
            foreach ($benchmarks as &$benchmark) {
              $entry = array();
              $entry['title'] = array_key_exists('title', $benchmark) && strlen($benchmark['title']) ? $benchmark['title'] : $benchmark['name'];
              $entry['configurations'] = array();
              foreach ($benchmark['configurations'] as $name => &$config) {
                $entry['configurations'][$name] = array();
                $entry['configurations'][$name]['title'] = htmlspecialchars(array_key_exists('title', $config) && strlen($config['title']) ? $config['title'] : $name);
                $entry['configurations'][$name]['locations'] = array();
                foreach ($config['locations'] as $location)
                  $entry['configurations'][$name]['locations'][] = htmlspecialchars($location);
              }
              $bmData[$benchmark['name']] = $entry;
            }
            echo "var benchmarks = " . json_encode($bmData) . ";\n";
            ?>
            function CompareTo(benchmark, config, location, time, title) {
              if (compareTo === undefined) {
                compareTo = {'title' : title,
                             'benchmark' : benchmark,
                             'config' : config,
                             'location' : location,
                             'time' : time};
              } else {
                var url = "compare.php?configs=";
                url += encodeURIComponent(compareTo['benchmark']);
                url += '~' + encodeURIComponent(compareTo['config']);
                url += '~' + encodeURIComponent(compareTo['location']);
                url += '~' + compareTo['time'];
                url += ',' + encodeURIComponent(benchmark);
                url += '~' + encodeURIComponent(config);
                url += '~' + encodeURIComponent(location);
                url += '~' + time;
                var offset = new Date().getTimezoneOffset();
                url += '&offset=' + encodeURIComponent(offset);
                window.location.href = url;
              }
              $.modal.close();
            }
            
            function SelectedPoint(benchmark, metric, series, time, cached) {
                time = parseInt(time / 1000, 10);
                var isCached = 0;
                if (cached)
                    isCached = 1;
                var menu = '<div><h4>View details:</h4>';
                var scatter = "viewtest.php?benchmark=" + encodeURIComponent(benchmark) + "&metric=" + encodeURIComponent(metric) + "&cached=" + isCached + "&time=" + time;
                var delta = "delta.php?benchmark=" + encodeURIComponent(benchmark) + "&metric=" + encodeURIComponent(metric) + "&time=" + time;
                menu += '<a href="' + scatter + '">Scatter Plot</a><br>';
                menu += '<a href="' + delta + '">Comparison Distribution</a><br>';
                menu += '<br>';
                if (compareTo === undefined)
                  menu += '<h4>Compare</h4>';
                else
                  menu += '<h4>Compare ' + compareTo['title'] + ' to:</h4>';
                for (config in benchmarks[benchmark]['configurations']) {
                  for (index in benchmarks[benchmark]['configurations'][config]['locations']) {
                    var location = benchmarks[benchmark]['configurations'][config]['locations'][index];
                    var title = benchmarks[benchmark]['configurations'][config]['title'];
                    if (benchmarks[benchmark]['configurations'][config]['locations'].length > 1)
                      title += ' ' + location;
                    trailer = '';
                    if (compareTo === undefined)
                      trailer = ' to...';
                    menu += '<a href="#" onclick="CompareTo(\'' + benchmark + '\',\'' 
                            + config + '\',\'' 
                            + location + '\',' 
                            + time + ',\'' 
                            + title + '\');return false;">' + title + trailer + '</a><br>';
                  }
                }
                menu += '</div>';
                $.modal(menu, {overlayClose:true});
            }
            </script>
            <div class="translucent">
            <div style="clear:both;">
                <div style="float:left;" class="notes">
                    Click on a test heading to view all of the metrics for the given test.<br>
                    Click on a data point in the chart to see the scatter plot results for that specific test.<br>
                    Highlight an area of the chart to zoom in on that area and double-click to zoom out.
                </div>
                <div style="float: right;">
                    <form name="aggregation" method="get" action="index.php">
                        Aggregation <select name="aggregate" size="1" onchange="this.form.submit();">
                            <option value="avg" <?php if ($aggregate == 'avg') echo "selected"; ?>>Average</option>
                            <option value="geo-mean" <?php if ($aggregate == 'geo-mean') echo "selected"; ?>>Geometric Mean</option>
                            <option value="median" <?php if ($aggregate == 'median') echo "selected"; ?>>Median</option>
                            <option value="75pct" <?php if ($aggregate == '75pct') echo "selected"; ?>>75th Percentile</option>
                            <option value="95pct" <?php if ($aggregate == '95pct') echo "selected"; ?>>95th Percentile</option>
                            <option value="stddev" <?php if ($aggregate == 'stddev') echo "selected"; ?>>Standard Deviation</option>
                            <option value="count" <?php if ($aggregate == 'count') echo "selected"; ?>>Count</option>
                        </select>
                        <br>
                        Time Period <select name="days" size="1" onchange="this.form.submit();">
                            <option value="7" <?php if ($days == 7) echo "selected"; ?>>Week</option>
                            <option value="31" <?php if ($days == 31) echo "selected"; ?>>Month</option>
                            <option value="93" <?php if ($days == 93) echo "selected"; ?>>3 Months</option>
                            <option value="183" <?php if ($days == 183) echo "selected"; ?>>6 Months</option>
                            <option value="366" <?php if ($days == 366) echo "selected"; ?>>Year</option>
                            <option value="all" <?php if ($days == 0) echo "selected"; ?>>All</option>
                        </select>
                    </form>
                </div>
            </div>
            <div style="clear:both;">
            </div>
            <?php
}
            $count = 0;
            foreach ($benchmarks as &$benchmark) {
                if (array_key_exists('title', $benchmark))
                    $title = $benchmark['title'];
                else
                    $title = $benchmark['name'];
                $bm = urlencode($benchmark['name']);
                if (!isset($out_data)) {
                    echo "<h2><a href=\"view.php?benchmark=$bm&aggregate=$aggregate\">$title</a> <span class=\"small\">(<a name=\"{$benchmark['name']}\" href=\"#{$benchmark['name']}\">direct link</a>)</span></h2>\n";
                    if (array_key_exists('description', $benchmark))
                        echo "{$benchmark['description']}<br>\n";
                }
                if (is_file("./results/benchmarks/{$benchmark['name']}/state.json")) {
                    $state = json_decode(file_get_contents("./results/benchmarks/{$benchmark['name']}/state.json"), true);
                  if (array_key_exists('running', $state) && $state['running'] &&
                      array_key_exists('tests', $state) && is_array($state['tests'])) {
                    $elapsed = 0;
                    $completed = 0;
                    $total = 0;
                    $now = time();
                    if ($now > $state['last_run'])
                      $elapsed = $now - $state['last_run'];
                    $total = count($state['tests']);
                    foreach ($state['tests'] as &$test) {
                      if (is_array($test) && array_key_exists('completed', $test) && $test['completed'])
                        $completed++;
                    }
                    if ($total) {
                      $hours = intval(floor($elapsed / 3600));
                      $elapsed -= $hours * 3600;
                      $minutes = intval(floor($elapsed / 60));
                      echo "<a href=\"partial.php?benchmark=$bm\">Benchmark is running</a> - completed $completed of $total tests in $hours hours and $minutes minutes.<br>";
                    }
                  }
                }
                echo '<br>';
                if ($benchmark['expand'] && count($benchmark['locations'] > 1)) {
                    foreach ($benchmark['locations'] as $location => $label) {
                        if (is_numeric($label))
                            $label = $location;
                        DisplayBenchmarkData($benchmark, $location, $label);
                    }
                } else {
                    DisplayBenchmarkData($benchmark);
                }
            }
if (!isset($out_data)) {
            ?>
            </div>
            
            <?php include('footer.inc'); ?>
        </div>
    </body>
</html>
<?php
} else {
    // spit out the raw data
    header ("Content-type: application/json; charset=utf-8");
    echo json_encode($out_data);
}

/**
* Display the charts for the given benchmark
* 
* @param mixed $benchmark
*/
function DisplayBenchmarkData(&$benchmark, $loc = null, $title = null) {
    global $count;
    global $aggregate;
    global $out_data;
    global $INCLUDE_ERROR_BARS;
    $label = 'Speed Index (First View)';
    $chart_title = '';
    if (isset($title))
        $chart_title = "title: \"$title (First View)\",";
    $bmname = $benchmark['name'];
    if (isset($loc)) {
        $bmname .= ".$loc";
    }
    $errorBars = $INCLUDE_ERROR_BARS && $aggregate == 'median' ? 'customBars: true,' : '';
    $tsv = LoadDataTSV($benchmark['name'], 0, 'SpeedIndex', $aggregate, $loc, $annotations);
    $metric = 'SpeedIndex';
    if (!isset($tsv) || !strlen($tsv)) {
        $label = 'Time to onload (First View)';
        $tsv = LoadDataTSV($benchmark['name'], 0, 'docTime', $aggregate, $loc, $annotations);
        $metric = 'docTime';
    }
    if ($aggregate == 'count')
        $label = 'Successful Tests (First View)';
    if (isset($out_data)) {
        $out_data[$bmname] = array();
        $out_data[$bmname][$metric] = array();
        $out_data[$bmname][$metric]['FV'] = TSVEncode($tsv);
    }
    if (!isset($out_data) && isset($tsv) && strlen($tsv)) {
        $count++;
        $id = "g$count";
        echo "<div class=\"chart-container\"><div id=\"$id\" class=\"benchmark-chart\"></div><div id=\"{$id}_legend\" class=\"benchmark-legend\"></div></div>\n";
        echo "<script type=\"text/javascript\">
                $id = new Dygraph(
                    document.getElementById(\"$id\"),
                    \"" . str_replace("\t", '\t', str_replace("\n", '\n', $tsv)) . "\",
                    {drawPoints: true,
                    rollPeriod: 1,
                    showRoller: true,
                    labelsSeparateLines: true,
                    colors: ['#ed2d2e', '#008c47', '#1859a9', '#662c91', '#f37d22', '#a11d20', '#b33893', '#010101'],
                    $chart_title
                    $errorBars
                    labelsDiv: document.getElementById('{$id}_legend'),
                    pointClickCallback: function(e, p) {SelectedPoint(\"{$benchmark['name']}\", \"$metric\", p.name, p.xval, false);},
                    legend: \"always\",
                    xlabel: \"Date\",
                    ylabel: \"$label\"}
                );\n";
        if (isset($annotations) && count($annotations)) {
            echo "$id.setAnnotations(" . json_encode($annotations) . ");\n";
        }
        echo "</script>\n";
    }
    if (!array_key_exists('fvonly', $benchmark) || !$benchmark['fvonly']) {
        $label = 'Speed Index (Repeat View)';
        if (isset($title))
            $chart_title = "title: \"$title (Repeat View)\",";
        $tsv = LoadDataTSV($benchmark['name'], 1, 'SpeedIndex', $aggregate, $loc, $annotations);
        $metric = 'SpeedIndex';
        if (!isset($tsv) || !strlen($tsv)) {
            $label = 'Time to onload (Repeat View)';
            $tsv = LoadDataTSV($benchmark['name'], 1, 'docTime', $aggregate, $loc, $annotations);
            $metric = 'docTime';
        }
        if ($aggregate == 'count')
            $label = 'Successful Tests (Repeat View)';
        if (isset($out_data)) {
            $out_data[$bmname][$metric]['RV'] = TSVEncode($tsv);
        }
        if (!isset($out_data) && isset($tsv) && strlen($tsv)) {
            $count++;
            $id = "g$count";
            echo "<div class=\"chart-container\"><div id=\"$id\" class=\"benchmark-chart\"></div><div id=\"{$id}_legend\" class=\"benchmark-legend\"></div></div>\n";
            echo "<script type=\"text/javascript\">
                    $id = new Dygraph(
                        document.getElementById(\"$id\"),
                        \"" . str_replace("\t", '\t', str_replace("\n", '\n', $tsv)) . "\",
                        {drawPoints: true,
                        rollPeriod: 1,
                        showRoller: true,
                        labelsSeparateLines: true,
                        colors: ['#ed2d2e', '#008c47', '#1859a9', '#662c91', '#f37d22', '#a11d20', '#b33893', '#010101'],
                        $chart_title
                        $errorBars
                        labelsDiv: document.getElementById('{$id}_legend'),
                        pointClickCallback: function(e, p) {SelectedPoint(\"{$benchmark['name']}\", \"$metric\", p.name, p.xval, true);},
                        legend: \"always\",
                        xlabel: \"Date\",
                        ylabel: \"$label\"}
                    );";
            if (isset($annotations) && count($annotations)) {
                echo "$id.setAnnotations(" . json_encode($annotations) . ");\n";
            }
            echo "</script>\n";
        }
    }
}
?>