<?php

interface DiceInterface {

  public function rollDice($die, $direction);
  public function getDirection($direction);
  public function getDirections();
}

class DiceBase implements DiceInterface {

  public function rollDice($die, $direction) {
    return $die;
  }

  public function getDirection($direction) {
    return $this->getDirections()[$direction];
  }

  public function getDirections() {
    return  [
      'L' => 0,
      'R' => 1,
      'F' => 2,
      'B' => 4,
    ];
  }
}

class staticDie extends DiceBase {

  private $die;
  private $dice_table;


  public function __construct() {
    $this->dice_table = json_decode('{"12":[[1,2,5,4,3,6],"51","26","32","42"],"13":[[1,3,4,2,5,6],"41","36","53","23"],"14":[[1,4,3,5,2,6],"31","46","24","54"],"15":[[1,5,2,3,4,6],"21","56","45","35"],"21":[[2,1,6,3,4,5],"62","15","41","31"],"23":[[2,3,4,6,1,5],"42","35","13","63"],"24":[[2,4,3,1,6,5],"32","45","64","14"],"26":[[2,6,1,4,3,5],"12","65","36","46"],"31":[[3,1,6,5,2,4],"63","14","21","51"],"32":[[3,2,5,1,6,4],"53","24","62","12"],"35":[[3,5,2,6,1,4],"23","54","15","65"],"36":[[3,6,1,2,5,4],"13","64","56","26"],"41":[[4,1,6,2,5,3],"64","13","51","21"],"42":[[4,2,5,6,1,3],"54","23","12","62"],"45":[[4,5,2,1,6,3],"24","53","65","15"],"46":[[4,6,1,5,2,3],"14","63","26","56"],"51":[[5,1,6,4,3,2],"65","12","31","41"],"53":[[5,3,4,1,6,2],"45","32","63","13"],"54":[[5,4,3,6,1,2],"35","42","14","64"],"56":[[5,6,1,3,4,2],"15","62","46","36"],"62":[[6,2,5,3,4,1],"56","21","42","32"],"63":[[6,3,4,5,2,1],"46","31","23","53"],"64":[[6,4,3,2,5,1],"36","41","54","24"],"65":[[6,5,2,4,3,1],"26","51","35","45"]}', true);
  }

  public function rollDice($die, $direction) {
    $new_die = $die;
    $index = $die[0].$die[1];
    $new_die = $this->dice_table[$this->dice_table[$index][($direction < 4) ? $direction + 1:$direction ]][0];
    return $new_die;
  }
}

class dynamicDie extends DiceBase {

  public function rollDice($die, $direction) {
    $new_die = $die;
    $btm = $die[5];

    $x = $direction % 2 ? 1:(0-1);

    $new_die[5] = $die[$direction];
    $new_die[$direction] = $die[0];
    $new_die[0] = $die[$direction + $x];
    $new_die[$direction + $x] = $btm;

    return $new_die;
  }

  public function getDirections() {
    return [
      'L' => 1,
      'R' => 2,
      'F' => 3,
      'B' => 4
    ];
  }
}

class diceRoller {

  private $die;

  public function __construct(DiceInterface $die) {
    $this->die = $die;
    return $this;
  }

  private function getRandomDirection() {
    $directions = ['L','R','F','B'];
    return $this->die->getDirection($directions[rand(0,3)]);
  }

  public function timeRolls($rolls = 1) {
    $start = microtime(true);
    $this->roll($rolls);
    $stop = microtime(true);
    return $stop - $start;
  }

  public function roll($count = 1, $set_direction = FALSE ) {
    // Hardcode first row, as to always start the same way.
    // order: T, L, R, F, B, Btm
    $die = [1,2,5,4,3,6];
    if(!$set_direction) {
      $direction = $this->getRandomDirection();
    }
    else {
      $set_direction  = $this->die->getDirection($set_direction);
      $direction = $set_direction;
    }

    while (--$count > 0) {
      $this->die->rollDice($die, $direction);
      $direction = $set_direction!==false ? $set_direction:$this->getRandomDirection();
    }
  }
}

class diceTester {

  private $rolls;
  private $dice;
  private $reports;
  private $start;
  private $stop;

  public function __construct($rolls, $dice) {
    $this->rolls = $rolls;
    $this->dice = $dice;
  }

  public function timeStart() {
    $this->start = microtime(true);
    return $this;
  }

  public function timeStop() {
    $this->stop = microtime(true);
    return $this;
  }

  public function timeTaken() {
    return $this->stop - $this->start;
  }

  public function compareDiceRolls($test_name = 'Compare', $print_report = FALSE) {
    $groups = $this->groupDice();
    $directions = ['L','R','F','B'];
    $report = [];
    foreach ($directions as $direction) {
      foreach ($groups as $type => $dice) {
        $face = [1,2,5,4,3,6];
        $die = $this->dice[$dice[0]];
        $report[$direction][$type] = $die->rollDice($face, $die->getDirection($direction));
      }
    }

    $this->reports['compare'][$test_name]['runs'][] = $report;

    if ($print_report) {
      $print_report = '<h2>' . $test_name . ' - Run ' . count($this->reports[$test_name]['runs']) .'</h2>';
      $print_report .= $this->printReport($report, 'compare');
      echo $print_report;
    }
    return $this;

  }

  public function testDiceRolls($test_name = 'Rolls',$print_report = FALSE) {
    $report = [];
    foreach ($this->dice as $name => $die) {
      $roller = new diceRoller($die);
      $report[$name] = $roller->timeRolls($this->rolls);
    }

    $this->reports['roll'][$test_name]['runs'][] = $report;

    if ($print_report) {
      $print_report = '<h2>' . $test_name . ' - Run ' . count($this->reports[$test_name]['runs']) .'</h2>';
      $print_report .= $this->printReport($report, 'roll');
      echo $print_report;
    }
    return $this;
  }

  public function showReport() {
    foreach ($this->reports as $type => $tests) {
      foreach ($tests as $name => $data) {
        echo '<h2>' . $type . ': ' . $name . '</h2><br />';
        foreach ($data['runs'] as $r => $report) {
          echo '<b>Run ' . ($r + 1) . '</b><br />';
          echo $this->printReport($report, $type);
          echo '<br />';
        }
      }
    }
  }

  private function printReport($report, $type = FALSE) {

    $columns = ['Die'];
    $data = [];

    switch ($type) {
      case 'roll':

        $columns[] = 'Time';
        foreach ($report as $die => $time) {
          $data[] = [$die,$time];
        }

        break;
      case 'compare':
        $columns[] = 'Direction';
        $columns[] = 'Type';
        $columns[] = 'Result';

        foreach ($report as $direction => $types) {
          foreach ($types as $die => $face) {
            $data[] = [$die,$direction,$type,implode('',$face)];
          }
        }

        break;
    }

    $html = '<table><thead><tr><th>'.implode('</th><th>', $columns).'</th></tr></thead>';
    foreach ($data as &$row) {
      $row = '<td>'.implode('</td><td>',$row);
    }
    $html .= '<tbody><tr>'.implode('</tr><tr>',$data).'</tr></tbody></table>';

    return $html;
  }

  private function groupDice() {
    $groups = [];
    foreach ($this->dice as $name => $die) {
      $type = preg_replace('/\d+/','',$name);
      $groups[$type][] = $name;
    }
    return $groups;
  }

  private function totalDiceTime($dice_names, $report) {
    $total = 0;
    foreach ($report['runs'] as $run) {
      foreach ($dice_names as $name) {
        $total += $run[$name];
      }
    }

    return $total;
  }

  private function avgDiceTime($dice_names, $data) {
    $total = $this->totalDiceTime($dice_names, $data);
    return $total / count($dice_names);
  }

  public function analyze($test_type = false) {



    $groups = $this->groupDice();



    foreach ($this->reports as $name => $data) {
      echo '<h2> TEST: '.$name.'</h2><br />';
      $stats = [];
      foreach ($groups as $type => $dice_names) {
        $stats[$type]=[
          'count' => count($dice_names),
          'time' => $this->totalDiceTime($dice_names, $data),
          'avg' => $this->avgDiceTime($dice_names, $data),
        ];
      }
    }

    echo '<b>Dice:</b>: '.count($this->dice).'<br />';
    foreach ($groups as $type => $dice_names) {
      echo "-- " . $type . ': '.$stats[$type]['count'].'<br />';
    }
    echo '<b>Time Taken:</b> '. $this->timeTaken() .'<br />';
    foreach ($groups as $type => $dice_names) {
      echo "-- " . $type . ': '.$stats[$type]['time'].'<br />';
    }
    echo '<b>Avg Time:</b>:' . ($this->timeTaken() / count($this->dice)) . '<br />';
    foreach ($groups as $type => $dice_names) {
      echo "-- " . $type . ': '.$stats[$type]['avg'].'<br />';
    }
    echo '<b>Fastest:</b> '. $this->fastestType($stats) .'<br />';
    echo '<b>Slowest:</b>'. $this->slowestType($stats) . '<br />';
  }

  private function fastestType($stats) {
    $times = [];
    foreach ($stats as $type => $data) {
      $times[$type] = $data['avg'];
    }
    sort($times);
    $times = array_keys($times);
    return $times[0];
  }
  private function slowestType($stats) {
    $times = [];
    foreach ($stats as $type => $data) {
      $times[$type] = $data['avg'];
    }
    rsort($times);
    $times = array_keys($times);
    return $times[0];
  }
}

function rollDice($die,$dice,&$map) {
  $directions = $dice->getDirections();
  $i = $die[0].$die[1];
  if (!isset($map[$i])) {
    $map[$i][0] = $die;
    foreach ($directions as $direction) {
      $roll = $dice->rollDice($die, $direction);
      $map[$i][$direction] = $roll[0].$roll[1];
      rollDice($roll,$dice,$map);
    }
  }
}

function generateStaticMap() {
  $map = [];
  $die = [1,2,5,4,3,6];

  rollDice($die,new dynamicDie(),$map);

  ksort($map);
  return $map;
}


//$map = generateStaticMap();
//foreach ($map as $index => $data) {
//  echo '<b>'.$index.'</b>: '.implode('',$data[0]).'<br />';
//}
//echo json_encode($map);


function random_dice() {
  $dice = [];
  $pool = rand(10,1000);
  for($x=0;$x<$pool;$x++) {
    $y=rand(1,2);
    if ($x%2) {
      $dice['Static'.$x] = new staticDie();
    }
    else {
      $dice['Dynamic'.$x] = new dynamicDie();
    }
  }
  return $dice;
}


$dice = [
  'Static' => new staticDie(),
  'Dynamic' => new dynamicDie()
];
$dice = random_dice();
$tester = new diceTester(100000, $dice);
$tester->timeStart()->testDiceRolls()->testDiceRolls()->timeStop()->analyze();
$tester->compareDiceRolls()->showReport();

