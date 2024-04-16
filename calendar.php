<?php
/**
 * 日历
 * @author ken <yiiphp@foxmail.com>
 */
/**
 * 生成日历table
 */
function gen_calendar_table($opt)
{
    global $vue;
    $vue_data_name = $opt['vue_data_name'] ?: "calendar_data";
    $year  = $opt['year'];
    $month = $opt['month'];
    $usually = $opt['usually'] ?: [1,2,3,4,5];
    $holiday = $opt['holiday'];
    $workday = $opt['workday'];
    $html = $opt['html'];
    $click = $opt['click'];
    $data = gen_calendar_data($year, $month);
    $d = date("d");
    $today = date("Y-m-d");
    foreach($data as $k => $v) {
        foreach($v as $kk => $vv) {
            $class = '';
            $month_tip = '';
            $tip = '';
            $date = $vv['year'].'-'.$vv['month'].'-'.$vv['date'];
            $date = date("Y-m-d", strtotime($date));
            if($vv['month'] < $month) {
                $month_tip = '上月';
            }
            if($vv['month'] == $month) {
                $month_tip = '本月';
                if($today == $date) {
                    $month_tip = '今天';
                }
            }
            if($vv['month'] > $month) {
                $month_tip = '下月';
            }
            if($vv['is_weekend']) {
                $class .= " weekend ";
                if($workday && in_array($date, $workday)) {
                    $class = 'holiday holiday-work ';
                    $tip = "班";
                }
            }
            if($holiday && in_array($date, $holiday)) {
                $class = ' holiday ';
                $tip = "休";
            }
            $data[$k][$kk]['month_tip'] = $month_tip;
            $data[$k][$kk]['tip'] = $tip;
            $data[$k][$kk]['class'] = $class;
        }
    }
    if(!$html) {
        return $data;
    }
    $vue->data($vue_data_name, "js:".json_encode($data));
    ob_start();
    ?>
<div class="sy_calendar">
    <div class="month"><?=$year?>年<?=$month?>月</div>
    <ul class="week">
        <li>一</li>
        <li>二</li>
        <li>三</li>
        <li>四</li>
        <li>五</li>
        <li class="weekend">六</li>
        <li class="weekend">日</li>
    </ul>
    <ul v-for="v in <?=$vue_data_name?>">
        <li :class="vv.class" v-for="vv in v">
            <div <?php if($click) {?>@click="<?=$click?>(vv.full)" <?php }?>>
                <b>{{vv.date}}</b>
                <i v-if="vv.title">{{vv.title}}</i>
                <u v-if="vv.tip">{{vv.tip}}</u>
            </div>
        </li>
    </ul>
</div>

<?php
    $content = ob_get_contents();
    ob_get_clean();
    return $content;
}
/**
 * 生成日历数组
 */
function gen_calendar_data($year, $month)
{
    $cur = gen_calendar($year, $month);
    $pre_month = date("Y-m", strtotime($year.'-'.$month.'-01'." -1 month"));
    $next_month = date("Y-m", strtotime($year.'-'.$month.'-01'." +1 month"));
    $arr = explode("-", $pre_month);
    $pre =  gen_calendar($arr[0], $arr[1]);
    $arr = explode("-", $next_month);
    $next =  gen_calendar($arr[0], $arr[1]);
    $cur = _gen_calendar_append($pre, $cur, 'pre');
    $cur = _gen_calendar_append($next, $cur, 'next');
    return $cur;
}
/**
 * 生成某年某月的周数组
 */
function gen_calendar($year, $month, $workday = [1,2,3,4,5])
{
    $weekend = array_diff([1,2,3,4,5,6,0], $workday);
    $calendar = array();
    // 获取指定年份和月份的第一天和最后一天
    $first_day = mktime(0, 0, 0, $month, 1, $year);
    $last_day  = mktime(0, 0, 0, $month, date('t', $first_day), $year);
    // 循环遍历每一天，将日期信息存入二维数组
    $current_day = $first_day;
    $last_key = '';
    while ($current_day <= $last_day) {
        // W 一年中的第几周 w周几 j一个月的第几天
        $key = date('W', $current_day);
        $w   = date('w', $current_day);
        $y   = date('Y', $current_day);
        $m   = date('m', $current_day);
        $j   = date('j', $current_day);
        $full = $y."-".$m.'-'.$j;
        $full = date("Y-m-d", strtotime($full.' 00:00:01'));
        $calendar[$key][date('w', $current_day)] = [
           'week_num' => $key,
           'year'  => $y,
           'month' => $m,
           'date'  => $j,
           'full'  => $full,
           'w'  => $w,
           'is_workday' => in_array($w, $workday) ? true : false,
           'is_weekend' => in_array($w, $weekend) ? true : false,
        ];
        $current_day = strtotime('+1 day', $current_day);
    }
    return $calendar;
}
/**
 * 向日历前、后补全整周
 */
function _gen_calendar_append($pre_or_last, $cur, $type = 'next')
{
    $cur_first_key = array_key_first($cur);
    $cur_last_key = array_key_last($cur);
    $firt_key = array_key_first($pre_or_last);
    $last_key = array_key_last($pre_or_last);
    $less = 7 - count(current($cur));
    $last_less = 7 - count(end($cur));
    if($less > 0) {
        if($type == 'pre') {
            $find = $pre_or_last[$last_key];
            $find = array_reverse($find, true);
            $j = 0;
            foreach($find as $k => $v) {
                if($j < $less) {
                    $cur[$cur_first_key] = [$k => $v] + $cur[$cur_first_key];
                }
                $j++;
            }
        } elseif($type == 'next') {
            $find = $pre_or_last[$firt_key];
            $j = 0;
            foreach($find as $k => $v) {
                if($j < $last_less) {
                    $cur[$cur_last_key] = $cur[$cur_last_key] + [$k => $v];
                }
                $j++;
            }
        }
    }
    return $cur;
}
