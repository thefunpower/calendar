<?php
/**
 * 日历
 * @author ken <yiiphp@foxmail.com>
 */

class calendar_table
{
    public static $_data;

    public static function set($key, $value)
    {
        self::$_data[$key] = $value;
    }

    public static function get($key)
    {
        return self::$_data[$key];
    }

    /**
     * 日历
     * @author ken <yiiphp@foxmail.com>
     */
    public static function ajax($opt = [])
    {
        $usually = self::get('usually') ?: [1,2,3,4,5];
        $holiday = self::get('holiday');
        $workday = self::get('workday');
        $year  = $opt['year'];
        $month = $opt['month'];
        $type = $opt['type'];
        if(!$year || !$month) {
            return json_error(['msg' => '']);
        }
        if($type == 1) {
            $type = " +1";
        } elseif($type == -1) {
            $type = " -1";
        }
        if($type) {
            $now = date("Y-m", strtotime($year.'-'.$month.$type."month"));
            $arr = explode("-", $now);
            $year = $arr[0];
            $month = $arr[1];
        }
        $data = self::create([
            'year' => $year,
            'month' => $month,
            'usually' => $usually,
            'holiday' => $holiday,
            'workday' => $workday,
        ]);
        return json_success([
            'data' => array_values($data),
            'year' => $year,
            'month' => $month,
        ]);
    }
    /**
     * 生成日历table
     */
    public static function create($opt)
    {
        global $vue;
        $url   = $opt['url'] ?: self::get('url');
        $year  = $opt['year'];
        $month = $opt['month'];
        $vue_data_name = $opt['vue_data_name'] ?: "calendar_data";
        $year_list = $opt['year_list'] ?: [
            date("Y", strtotime("-1 year")),
            date("Y"),
            date("Y", strtotime("+1 year")),
        ];
        $month_list = [
            '01' => '1月',
            '02' => '2月',
            '03' => '3月',
            '04' => '4月',
            '5' => '5月',
            '6' => '6月',
            '7' => '7月',
            '8' => '8月',
            '9' => '9月',
            '10' => '10月',
            '11' => '11月',
            '12' => '12月',
        ];
        $lookup = [];
        foreach($year_list as $_y) {
            foreach($month_list as $_k => $_v) {
                $lookup[] = [
                    'year' => $_y,
                    'month' => $_k,
                    'value' => $_v,
                ];
            }
        }
        $lookup_i = 0;
        foreach($lookup as $v) {
            if($v['year'] == $year && $v['month'] == $month) {
                $lookup_i++;
                break;
            }
        }
        $usually = self::get('usually') ?: [1,2,3,4,5];
        $holiday = self::get('holiday');
        $workday = self::get('workday');
        $html  = $opt['html'];
        $click = $opt['click'];
        $data = self::gen_calendar_data($year, $month);

        $d = date("d");
        $today = date("Y-m-d");
        foreach($data as $k => $v) {
            foreach($v as $kk => $vv) {
                $class = '';
                $month_tip = '';
                $append_class = '';
                $tip = '';
                $date = $vv['year'].'-'.$vv['month'].'-'.$vv['date'];
                $date = date("Y-m-d", strtotime($date));
                if($vv['month'] < $month) {
                    $month_tip = '上月';
                    $append_class =  ' gray ';
                }
                if($vv['month'] == $month) {
                    $month_tip = '本月';
                    if($today == $date) {
                        $month_tip = '今天';
                    }
                }
                if($vv['month'] > $month) {
                    $month_tip = '下月';
                    $append_class =  ' gray ';
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
                $actived = false;
                if($vv['date'] == date("d")) {
                    $actived = true;
                }
                $data[$k][$kk]['month_tip'] = $month_tip;
                $data[$k][$kk]['tip'] = $tip;
                $data[$k][$kk]['actived'] = $actived;
                $data[$k][$kk]['class'] = $class.$append_class;
            }
        }
        if(!$html) {
            return $data;
        }
        $vue->data("calendar_year", $year);
        $vue->data("calendar_year_list", "js:".json_encode($year_list));
        $vue->data("calendar_month", date("m", strtotime("2024-".$month)));
        $vue->data("calendar_month_list", "js:".json_encode($month_list));
        $vue->data("calendar_lookup_i", $lookup_i);
        $vue->data("calendar_lookup", "js:".json_encode($lookup));
        $vue->data($vue_data_name, "js:".json_encode($data));
        $vue->method("click_calendar_month(num)", "
            $.post('".$url."',{year:this.calendar_year,month:this.calendar_month,type:num},function(res){
                app.".$vue_data_name." = res.data;
                app.calendar_year = res.year;
                app.calendar_month = res.month; 
                app.\$forceUpdate();
            },'json');
        ");
        $vue->method("click_calendar_change()", "
            $.post('".$url."',{year:this.calendar_year,month:this.calendar_month},function(res){
                app.".$vue_data_name." = res.data;
                app.calendar_year = res.year;
                app.calendar_month = res.month; 
                app.\$forceUpdate();
            },'json');
        ");
        $vue->data("calendar_click_li_actived", '');
        $vue->data("calendar_table_show", false);
        $vue->created(["calendar_table_init()"]);
        $vue->method("calendar_table_init()", "
            $.post('".$url."',{year:this.calendar_year,month:this.calendar_month},function(res){
                app.".$vue_data_name." = res.data;
                app.calendar_year = res.year;
                app.calendar_month = res.month; 
                app.calendar_table_show = true;
                app.\$forceUpdate();
            },'json');
        ");
        ob_start();
        ?>
<div class="calendar_filter">
    <el-select v-model="calendar_year" @change="click_calendar_change" placeholder="年" style="width: 80px;">
        <el-option v-for="(v,k) in calendar_year_list" :key="v" :label="v" :value="v">
        </el-option>
    </el-select>
    <i class="el-icon-arrow-left" style="cursor:pointer;" @click="click_calendar_month(-1)"></i>
    <el-select v-model="calendar_month" @change="click_calendar_change" placeholder="月" style="width: 80px;">
        <el-option v-for="(v,k) in calendar_month_list" :key="k" :label="v" :value="k">
        </el-option>
    </el-select>
    <i class="el-icon-arrow-right" style="cursor:pointer;" @click="click_calendar_month(+1)"></i>
</div>
<div class="sy_calendar" v-if="calendar_table_show">
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
            <div v-if="vv.actived==1" class="actived" <?php if($click) {?>@click="<?=$click?>(vv.full)" <?php }?>
                :title="vv.full">
                <b>{{vv.date}}</b>
                <i v-if="vv.title">{{vv.title}}</i>
                <u v-if="vv.tip">{{vv.tip}}</u>
            </div>
            <div :class="calendar_click_li_actived==vv.full?'active':''  || vv.actived==1?'actived':''" v-else
                <?php if($click) {?>@click="<?=$click?>(vv.full)" <?php }?> :title="vv.full">
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
    public static function gen_calendar_data($year, $month)
    {
        $cur = self::gen_calendar($year, $month);
        $pre_month = date("Y-m", strtotime($year.'-'.$month.'-01'." -1 month"));
        $next_month = date("Y-m", strtotime($year.'-'.$month.'-01'." +1 month"));
        $arr = explode("-", $pre_month);
        $pre =  self::gen_calendar($arr[0], $arr[1]);
        $arr = explode("-", $next_month);
        $next =  self::gen_calendar($arr[0], $arr[1]);
        $cur = self::_gen_calendar_append($pre, $cur, 'pre');
        $cur = self::_gen_calendar_append($next, $cur, 'next');

        foreach($cur as $k => $v) {
            foreach($v as $kk => $vv) {
                if($kk == 0) {
                    $cur[$k][7] = $vv;
                    unset($cur[$k][$kk]);
                    continue;
                }
            }
        }
        return $cur;
    }
    /**
     * 生成某年某月的周数组
     */
    public static function gen_calendar($year, $month)
    {
        $workday = self::get('usually') ?: [1,2,3,4,5];
        $weekend = array_diff([1,2,3,4,5,6,7], $workday);
        $calendar = array();
        // 获取指定年份和月份的第一天和最后一天
        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $last_day  = mktime(0, 0, 0, $month, date('t', $first_day), $year);
        // 循环遍历每一天，将日期信息存入二维数组
        $current_day = $first_day;
        $last_key = '';
        $w_name = [
            1 => '周一',
            2 => '周二',
            3 => '周三',
            4 => '周四',
            5 => '周五',
            6 => '周六',
            0 => '周日',
        ];
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
               'w_name'  => $w_name[$w],
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
    public static function _gen_calendar_append($pre_or_last, $cur, $type = 'next')
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
}
