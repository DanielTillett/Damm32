# End-to-end tests for the damm32 command-line tool.
# Run via ctest; expects -DDAMM32=<path to executable>.

function(expect_run expected_status expected_output)
    execute_process(
        COMMAND ${DAMM32} ${ARGN}
        RESULT_VARIABLE status
        OUTPUT_VARIABLE output
        ERROR_QUIET)
    string(REPLACE "\r" "" output "${output}")
    if(NOT status EQUAL expected_status)
        message(FATAL_ERROR "damm32 ${ARGN}: exit ${status}, expected ${expected_status}")
    endif()
    if(NOT "${expected_output}" STREQUAL "*" AND NOT output STREQUAL "${expected_output}")
        message(FATAL_ERROR "damm32 ${ARGN}: got\n${output}\nexpected\n${expected_output}")
    endif()
endfunction()

expect_run(0 "Base32 Number: fdfsdfds\nBase32 Check Digit: 3\nError Check: 0\n" fdfsdfds)
expect_run(0 "Base32 Number: fdfsdfds3\nBase32 Check Digit: 2\nError Check: 1\n" fdfsdfds3)
expect_run(0 "fdfsdfds3\nabcq\n" -a fdfsdfds abc)
expect_run(0 "" -c fdfsdfds3)
expect_run(1 "" -c fdfsdfds3 fdfsdfds4)
expect_run(0 "9j*\n" -a 9j)
expect_run(0 "" -c 9j*)
expect_run(1 "*" -a bisantrene)
expect_run(2 "*" --bogus)
expect_run(0 "*" --help)
