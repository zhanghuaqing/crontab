<?php
/**
 * 单元测试底层
 *
 * 对调用页面的所有TestSuite子类进行调用并统计相关数据
 *
 * 调用example:
 *     UnitTest::run ();
 *
 * @author zhiyuan <zhiyuan12@staff.sina.com.cn>
 */
class UnitTest {
	/**
	 * 静态方法，调用后执行调用页所有TestSuite子对象
	 *
	 * @param object $displayer        	
	 */
	public static function run($displayer = null) {
		if ($displayer == null) {
			$displayer = new TestResultDisplayer ();
		}
		$passed_suite_num = 0;
		$total_suite_num = 0;
		$fail_cases = array ();
		$passed_case_count = 0;
		$total_case_count = 0;
		foreach ( get_declared_classes () as $class ) {
			if (is_subclass_of ( $class, "TestSuite" )) {
				++ $total_suite_num;
				$test_case = new $class ();
				if (! $test_case->run ( $displayer )) {
					$fail_cases = array_merge ( $fail_cases, $test_case->failCases () );
				} else {
					++ $passed_suite_num;
				}
				$passed_case_count += count ( $test_case->passCases () );
				$total_case_count += count ( $test_case->passCases () ) + count ( $test_case->failCases () );
			}
		}
		if ($total_case_count == $passed_case_count) {
			$displayer->pass ( "[PASSED] run all test suite passed" );
		} else {
			$displayer->fail ( "[FAILED] passed $passed_suite_num/$total_suite_num suite" );
			$displayer->fail ( "[FAILED] passed $passed_case_count/$total_case_count case(s)" );
			$displayer->normal ( "Failed Cases:" );
			foreach ( $fail_cases as $case ) {
				$displayer->fail ( "$case" );
			}
		}
	}
}
/**
 * 输出信息格式类
 */
class TestResultDisplayer {
	public function pass($msg) {
		echo "<p style=\"color:green\">$msg</p>\n";
	}
	public function fail($msg) {
		echo "<p style=\"color:red\">$msg</p>\n";
	}
	public function normal($msg) {
		echo "<p>$msg</p>";
	}
}