cmake_minimum_required(VERSION 3.16)

project(damm32
    VERSION 2.0.0
    DESCRIPTION "Base32 implementation of the Damm check digit algorithm"
    LANGUAGES C)

set(CMAKE_C_STANDARD 99)
set(CMAKE_C_STANDARD_REQUIRED ON)
set(CMAKE_C_EXTENSIONS OFF)

if(MSVC)
    set(DAMM32_WARNINGS /W4)
else()
    set(DAMM32_WARNINGS -Wall -Wextra -Wpedantic -Wconversion -Wshadow)
endif()

add_library(damm32lib src/damm32.c)
target_include_directories(damm32lib PUBLIC
    $<BUILD_INTERFACE:${CMAKE_CURRENT_SOURCE_DIR}/include>
    $<INSTALL_INTERFACE:include>)
target_compile_options(damm32lib PRIVATE ${DAMM32_WARNINGS})
set_target_properties(damm32lib PROPERTIES OUTPUT_NAME damm32)

add_executable(damm32 src/main.c)
target_link_libraries(damm32 PRIVATE damm32lib)
target_compile_options(damm32 PRIVATE ${DAMM32_WARNINGS})
target_compile_definitions(damm32 PRIVATE DAMM32_VERSION="${PROJECT_VERSION}")

include(GNUInstallDirs)
install(TARGETS damm32 damm32lib)
install(FILES include/damm32.h DESTINATION ${CMAKE_INSTALL_INCLUDEDIR})

include(CTest)
if(BUILD_TESTING)
    add_executable(test_damm32 tests/test_damm32.c)
    target_link_libraries(test_damm32 PRIVATE damm32lib)
    target_compile_options(test_damm32 PRIVATE ${DAMM32_WARNINGS})
    add_test(NAME unit COMMAND test_damm32 ${CMAKE_CURRENT_SOURCE_DIR}/tests/vectors.txt)
    add_test(NAME cli
        COMMAND ${CMAKE_COMMAND} -DDAMM32=$<TARGET_FILE:damm32>
                -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/test_cli.cmake)
endif()
