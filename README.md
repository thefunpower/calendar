# 日历 

## 安装

~~~
composer require thefunpower/calendar
~~~

## 使用

~~~
$year = 2026;
$month = 4; 
$calendar = gen_calendar_table([
  'year'=>$year,
  'month'=>$month,
  'usually'=>[1,2,3,4,5],
  'holiday'=>['2026-04-01','2026-04-08'],
  'workday'=>['2026-04-05','2026-04-18','2026-04-25',],
  'html'=>1,
  'click'=>'calendar',
  'vue_data_name'=>'calendar_data',
]); 

$vue->method("calendar(date)"," 
  console.log(date);
");
~~~

输出

~~~
<?=$calendar?>
~~~

更新 `calendar_data` 值页面数据将自动更新


  

### 开源协议 

[Apache License 2.0](LICENSE)
