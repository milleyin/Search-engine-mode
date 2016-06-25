<?php

class Pagination{

    private $cfg_page = array();
    private $cfg_unit = 0;

    public function opt($cfg_page, $cfg_unit = 5) {
        //now样式
        $this->cfg_page['now'] = $cfg_page['now'];
        //other样式
        $this->cfg_page['other'] = $cfg_page['other'];
        //before样式
        $this->cfg_page['before'] = $cfg_page['before'];
        //after样式
        $this->cfg_page['after'] = $cfg_page['after'];
        //最后一页样式
        $this->cfg_page['last'] = $cfg_page['last'];
        //起始一页样式
        $this->cfg_page['first'] = $cfg_page['first'];
        //允许的页码按钮
        $this->cfg_unit = $cfg_unit;
    }

    public function make($total, $unit_now, $per_page = 10) {
        $unit_total = ceil($total / $per_page);
        $unit_per = $unit_now;
        $tmp_str = '';

        //cfg_unit上限处理
        if ($this->cfg_unit > $unit_total) {
            $this->cfg_unit = $unit_total;
        }

        //规范数据
        if ($unit_total < $unit_now) {
            return '';
        } elseif ($unit_now < 1) {
            $unit_now = 1;
        }

        //首先确定有没有first这个单元
        if ($unit_now > 2) {
            $tmp_str .= $this->strcore($this->cfg_page['first'], '1');
        }
        //不是第一页的话,则有before单元
        if ($unit_now > 1) {
            $tmp_str .= $this->strcore($this->cfg_page['before'], $unit_now - 1);
        }
        //start处理主要单元
        if ($unit_total > 0) {
            $add_var = ceil($unit_per / $this->cfg_unit) - 1;

            $start = $add_var * $this->cfg_unit + 1;
            $close = $add_var * $this->cfg_unit + $this->cfg_unit + 1;

            if ($start < 1) {
                $start = 1;
            }

            if ($close > $unit_total) {
                $close = $unit_total;
            }

            for ($i = 1; $i <= $this->cfg_unit; $i++) {
                //修正,如果当前指针大于total,则跳出.
                if ($start - $unit_total > 0) {
                    break;
                }

                if ($unit_now == $start) {
                    $tmp_str .= $this->strcore($this->cfg_page['now'], $start);
                } else {
                    $tmp_str .= $this->strcore($this->cfg_page['other'], $start);
                }
                $start++;
            }
        }
        //判断是否需要after单元
        if (($unit_total - $unit_now) > 0) {
            $tmp_str .= $this->strcore($this->cfg_page['after'], $unit_now + 1);
        }
        //判断是否需要尾页单元
        if ($unit_total - $unit_now > 1) {
            $tmp_str .= $this->strcore($this->cfg_page['last'], $unit_total);
        }

        return $tmp_str;
    }

    private function strcore($string, $change) {
        return str_replace('*', $change, $string);
    }

}

?>
