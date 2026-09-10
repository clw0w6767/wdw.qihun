<?php
class GoBoard {
    public $size = 19;
    public $board;
    public $ko = null;
    public $captured = [1 => 0, 2 => 0];
    public $history = [];

    public function __construct() { $this->reset(); }

    public function reset() {
        $this->board = array_fill(0, $this->size, array_fill(0, $this->size, 0));
        $this->ko = null;
        $this->captured = [1 => 0, 2 => 0];
        $this->history = [];
    }

    public function place($row, $col, $color) {
        if ($row<0 || $row>=$this->size || $col<0 || $col>=$this->size)
            return ['success'=>false, 'error'=>'超出棋盘'];
        if ($this->board[$row][$col] !== 0)
            return ['success'=>false, 'error'=>'此处已有棋子'];
        if ($this->ko && $this->ko['r']==$row && $this->ko['c']==$col)
            return ['success'=>false, 'error'=>'禁止打劫'];

        $opponent = $color==1 ? 2 : 1;
        $this->board[$row][$col] = $color;
        $captured = [];
        $dirs = [[-1,0],[1,0],[0,-1],[0,1]];

        foreach ($dirs as $d) {
            $nr=$row+$d[0]; $nc=$col+$d[1];
            if ($nr>=0 && $nr<$this->size && $nc>=0 && $nc<$this->size && $this->board[$nr][$nc]==$opponent) {
                $group = $this->getGroup($nr, $nc);
                if (!$this->hasLiberty($group)) $captured = array_merge($captured, $group);
            }
        }

        if (empty($captured)) {
            $selfGroup = $this->getGroup($row, $col);
            if (!$this->hasLiberty($selfGroup)) {
                $this->board[$row][$col] = 0;
                return ['success'=>false, 'error'=>'自杀禁手'];
            }
        }

        foreach ($captured as $p) {
            $this->board[$p['r']][$p['c']] = 0;
            $this->captured[$opponent] += 1;
        }

        $ko = null;
        if (count($captured)==1) {
            $p = $captured[0];
            $cnt = 0;
            foreach ($dirs as $d) {
                $nr=$p['r']+$d[0]; $nc=$p['c']+$d[1];
                if ($nr>=0 && $nr<$this->size && $nc>=0 && $nc<$this->size && $this->board[$nr][$nc]==$color) $cnt++;
            }
            if ($cnt==1) $ko = ['r'=>$p['r'], 'c'=>$p['c']];
        }
        $this->ko = $ko;
        $this->history[] = ['r'=>$row, 'c'=>$col, 'color'=>$color];
        return ['success'=>true, 'captured'=>count($captured), 'ko'=>$ko];
    }

    private function getGroup($row, $col) {
        $color = $this->board[$row][$col];
        if ($color==0) return [];
        $visited=[]; $queue=[[$row,$col]]; $group=[];
        while (!empty($queue)) {
            list($r,$c) = array_shift($queue);
            $k = $r*$this->size+$c;
            if (isset($visited[$k])) continue;
            $visited[$k]=true;
            $group[] = ['r'=>$r,'c'=>$c];
            foreach ([[-1,0],[1,0],[0,-1],[0,1]] as $d) {
                $nr=$r+$d[0]; $nc=$c+$d[1];
                if ($nr>=0 && $nr<$this->size && $nc>=0 && $nc<$this->size && $this->board[$nr][$nc]==$color) $queue[]=[$nr,$nc];
            }
        }
        return $group;
    }

    private function hasLiberty($group) {
        foreach ($group as $p) {
            foreach ([[-1,0],[1,0],[0,-1],[0,1]] as $d) {
                $nr=$p['r']+$d[0]; $nc=$p['c']+$d[1];
                if ($nr>=0 && $nr<$this->size && $nc>=0 && $nc<$this->size && $this->board[$nr][$nc]==0) return true;
            }
        }
        return false;
    }
}
?>
