export {}
import {load} from 'cheerio'
import {parseTestCase, parseTestSuite, parseTestSuites, processXmlResult,} from './index'

describe('parseTestCase', () => {
  test('parses passing test', () => {
    const xml = `
      <testcase name="test1"/>
    `
    const testCaseEl = load(xml)('testcase')
    const result = parseTestCase(testCaseEl)

    expect(result).toEqual({name: 'test1', status: 'pass', output: ''})
  })

  test('parses passing test with system output', () => {
    const xml = `
      <testcase name="test2">
        <system-out>Hello World!</system-out>
      </testcase>
    `
    const testCaseEl = load(xml)('testcase')
    const result = parseTestCase(testCaseEl)

    expect(result).toEqual({
      name: 'test2',
      status: 'pass',
      output: 'Hello World!',
    })
  })

  test('parses failing test', () => {
    const xml = `
      <testcase name="test3"/>
        <failure>This is a failure message</failure>
      </testcase>
    `
    const testCaseEl = load(xml)('testcase')
    const result = parseTestCase(testCaseEl)

    expect(result).toEqual({
      name: 'test3',
      status: 'fail',
      message: 'This is a failure message',
      output: '',
    })
  })

  test('parses failing test with system output', () => {
    const xml = `
      <testcase name="test4"/>
        <failure>This is a failure message</failure>
        <system-out>Goodbye World!</system-out>
      </testcase>
    `
    const testCaseEl = load(xml)('testcase')
    const result = parseTestCase(testCaseEl)

    expect(result).toEqual({
      name: 'test4',
      status: 'fail',
      message: 'This is a failure message',
      output: 'Goodbye World!',
    })
  })

  test('parses erroring test', () => {
    const xml = `
      <testcase name="test5"/>
        <error type="RuntimeException">This is an error message</error>
      </testcase>
    `
    const testCaseEl = load(xml)('testcase')
    const result = parseTestCase(testCaseEl)

    expect(result).toEqual({
      name: 'test5',
      status: 'error',
      message: 'This is an error message',
      output: '',
    })
  })

  test('parses erroring test', () => {
    const xml = `
      <testcase name="test6"/>
        <error type="RuntimeException">This is an error message</error>
        <system-out>Goodbye cruel world!</system-out>
      </testcase>
    `
    const testCaseEl = load(xml)('testcase')
    const result = parseTestCase(testCaseEl)

    expect(result).toEqual({
      name: 'test6',
      status: 'error',
      message: 'This is an error message',
      output: 'Goodbye cruel world!',
    })
  })
})

describe('parseTestSuite', () => {
  test('parses correctly', () => {
    const xml = `
      <testsuite name="HelloWorldTest" file="/home/tim/Projects/exercism/php-test-runner/test/hello-world/HelloWorldTest.php" tests="31" assertions="30" errors="1" warnings="0" failures="15" skipped="0" time="0.001204"/>
    `
    const testSuiteEl = load(xml)('testsuite')
    const result = parseTestSuite(testSuiteEl)

    expect(result).toEqual({
      tests: 31,
      assertions: 30,
      errors: 1,
      warnings: 0,
      failures: 15,
      skipped: 0,
    })
  })
})

describe('parseTestSuites', () => {
  test('parses testsuites with 0 testsuites', () => {
    const xml = `
      <?xml version="1.0" encoding="UTF-8"?>
      <testsuites />
    `
    const $ = load(xml)
    const result = parseTestSuites($)

    expect(result).toEqual([])
  })

  test('parses test suite with 1 testsuite, 0 test cases', () => {
    const xml = `
      <?xml version="1.0" encoding="UTF-8"?>
      <testsuites>
        <testsuite name="HelloWorldTest" file="/opt/php-test-runner/test/hello-world/HelloWorldTest.php" tests="0" assertions="0" errors="0" warnings="0" failures="0" skipped="0" time="0.000087" />
      </testsuites>
    `
    const $ = load(xml)
    const result = parseTestSuites($)

    expect(result).toEqual([
      {
        assertions: 0,
        errors: 0,
        failures: 0,
        skipped: 0,
        testCases: [],
        tests: 0,
        warnings: 0,
      },
    ])
  })

  test('parses test suite with 1 testsuite, 1 test cases', () => {
    const xml = `
      <?xml version="1.0" encoding="UTF-8"?>
      <testsuites>
        <testsuite name="HelloWorldTest" file="/opt/php-test-runner/test/hello-world/HelloWorldTest.php" tests="1" assertions="0" errors="1" warnings="0" failures="0" skipped="0" time="0.000087">
          <testcase name="testAssertEqualsPassing" class="HelloWorldTest" classname="HelloWorldTest" file="/opt/php-test-runner/test/hello-world/HelloWorldTest.php" line="10" assertions="0" time="0.000087">
            <error type="Error">HelloWorldTest::testAssertEqualsPassing
      Error: Call to undefined function helloWorld()</error>
          </testcase>
        </testsuite>
      </testsuites>
    `
    const $ = load(xml)
    const result = parseTestSuites($)

    expect(result).toEqual([
      {
        assertions: 0,
        errors: 1,
        failures: 0,
        skipped: 0,
        testCases: [
          {
            message:
              'HelloWorldTest::testAssertEqualsPassing\n      Error: Call to undefined function helloWorld()',
            name: 'testAssertEqualsPassing',
            output: '',
            status: 'error',
          },
        ],
        tests: 1,
        warnings: 0,
      },
    ])
  })

  test('parses 2 testsuites, each with tests', () => {
    const xml = fullXmlExample()
    const $ = load(xml)
    const result = parseTestSuites($)

    expect(result).toEqual([
      {
        assertions: 0,
        errors: 1,
        failures: 0,
        skipped: 0,
        testCases: [
          {
            message:
              'HelloWorldTest::testAssertEqualsPassing\n    Error: Call to undefined function helloWorld()',
            name: 'testAssertEqualsPassing',
            output: '',
            status: 'error',
          },
        ],
        tests: 1,
        warnings: 0,
      },
      {
        assertions: 1,
        errors: 0,
        failures: 0,
        skipped: 0,
        testCases: [
          {
            name: 'testAssertEqualsPassing',
            output: '',
            status: 'pass',
          },
        ],
        tests: 1,
        warnings: 0,
      },
    ])
  })
})

describe('processXmlResult', () => {
  test('correct format returned for failing', () => {
    const xml = fullXmlExample()
    const xmlBuffer = Buffer.from(xml)
    const result = processXmlResult(xmlBuffer)
    expect(result).not.toBeNull()
    expect(result.version).toEqual(2)
    expect(result.status).toEqual('fail')
    expect(result.tests).toHaveLength(2)
  })

  test('parses empty output as an error', () => {
    // If the student puts exit or die calls in their code, then PHPUnit will
    // exit without producing any output in the output.xml file.
    const xml = ``

    const xmlBuffer = Buffer.from(xml)
    const result = processXmlResult(xmlBuffer)

    expect(result).not.toBeNull()
    expect(result.version).toBe(2)
    expect(result.status).toEqual('error')
    expect(result.tests).toHaveLength(0)
    expect(result.message).toBe("Unit test run did not produce any results. Did the tests finish " +
      "completely?\n\nUsing the `die` function in your code will cause the test run to not produce any output.")
  })
})

function fullXmlExample() {
  return `
    <?xml version="1.0" encoding="UTF-8"?>
    <testsuites>
      <testsuite name="HelloWorldTestFail" file="/opt/php-test-runner/test/hello-world/HelloWorldTest.php" tests="1" assertions="0" errors="1" warnings="0" failures="0" skipped="0" time="0.000087">
        <testcase name="testAssertEqualsPassing" class="HelloWorldTest" classname="HelloWorldTest" file="/opt/php-test-runner/test/hello-world/HelloWorldTest.php" line="10" assertions="0" time="0.000087">
          <error type="Error">HelloWorldTest::testAssertEqualsPassing
    Error: Call to undefined function helloWorld()</error>
        </testcase>
      </testsuite>
      <testsuite name="HelloWorldTestPass" file="/opt/php-test-runner/test/hello-world/HelloWorldTest.php" tests="1" assertions="1" errors="0" warnings="0" failures="0" skipped="0" time="0.000095">
        <testcase name="testAssertEqualsPassing" class="HelloWorldTest" classname="HelloWorldTest" file="/opt/php-test-runner/test/hello-world/HelloWorldTest.php" line="10" assertions="0" time="0.000087"/>
      </testsuite>
    </testsuites>
  `
}

describe('secondXmlExample', () => {
  test('correct format returned for failing', () => {
    const xml = secondXmlExample()
    const xmlBuffer = Buffer.from(xml)
    const result = processXmlResult(xmlBuffer)
    expect(result).not.toBeNull()
    expect(result.version).toEqual(2)
    expect(result.status).toEqual('fail')
    expect(result.tests).toHaveLength(8)
  })
})

function secondXmlExample() {
  return `
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite name="HammingTest" file="/solution/HammingTest.php" tests="8" assertions="8" errors="0" warnings="0" failures="3" skipped="0" time="0.000761">
    <testcase name="testNoDifferenceBetweenIdenticalStrands" class="HammingTest" classname="HammingTest" file="/solution/HammingTest.php" line="35" assertions="1" time="0.000224"/>
    <testcase name="testCompleteHammingDistanceOfForSingleNucleotideStrand" class="HammingTest" classname="HammingTest" file="/solution/HammingTest.php" line="40" assertions="1" time="0.000051"/>
    <testcase name="testCompleteHammingDistanceForSmallStrand" class="HammingTest" classname="HammingTest" file="/solution/HammingTest.php" line="45" assertions="1" time="0.000049"/>
    <testcase name="testSmallHammingDistance" class="HammingTest" classname="HammingTest" file="/solution/HammingTest.php" line="50" assertions="1" time="0.000044"/>
    <testcase name="testSmallHammingDistanceInLongerStrand" class="HammingTest" classname="HammingTest" file="/solution/HammingTest.php" line="55" assertions="1" time="0.000048"/>
    <testcase name="testLargeHammingDistance" class="HammingTest" classname="HammingTest" file="/solution/HammingTest.php" line="60" assertions="1" time="0.000124">
      <failure type="PHPUnit\Framework\ExpectationFailedException">HammingTest::testLargeHammingDistance
Failed asserting that 0 matches expected 4.

/solution/HammingTest.php:62</failure>
    </testcase>
    <testcase name="testHammingDistanceInVeryLongStrand" class="HammingTest" classname="HammingTest" file="/solution/HammingTest.php" line="65" assertions="1" time="0.000127">
      <failure type="PHPUnit\Framework\ExpectationFailedException">HammingTest::testHammingDistanceInVeryLongStrand
Failed asserting that 0 matches expected 9.

/solution/HammingTest.php:67</failure>
    </testcase>
    <testcase name="testExceptionThrownWhenStrandsAreDifferentLength" class="HammingTest" classname="HammingTest" file="/solution/HammingTest.php" line="70" assertions="1" time="0.000095">
      <failure type="PHPUnit\Framework\ExpectationFailedException">HammingTest::testExceptionThrownWhenStrandsAreDifferentLength
Failed asserting that exception of type "InvalidArgumentException" is thrown.</failure>
    </testcase>
  </testsuite>
</testsuites>
  `
}

describe('multipleTestPassXmlResult', () => {
  test('correct format returned for failing', () => {
    const xml = multipleTestXmlResult()
    const xmlBuffer = Buffer.from(xml)
    const result = processXmlResult(xmlBuffer)
    expect(result).not.toBeNull()
    expect(result.version).toEqual(2)
    expect(result.status).toEqual('pass')
    expect(result.tests).toHaveLength(6)
  })
})

function multipleTestXmlResult() {
  return `
    <?xml version="1.0" encoding="UTF-8"?>
    <testsuites>
      <testsuite name="ReverseStringTest" file="/solution/ReverseStringTest.php" tests="6" assertions="6" errors="0" warnings="0" failures="0" skipped="0" time="0.000408">
        <testcase name="testEmptyString" class="ReverseStringTest" classname="ReverseStringTest" file="/solution/ReverseStringTest.php" line="13" assertions="1" time="0.000142"/>
        <testcase name="testWord" class="ReverseStringTest" classname="ReverseStringTest" file="/solution/ReverseStringTest.php" line="18" assertions="1" time="0.000059"/>
        <testcase name="testCapitalizedWord" class="ReverseStringTest" classname="ReverseStringTest" file="/solution/ReverseStringTest.php" line="23" assertions="1" time="0.000048"/>
        <testcase name="testSentenceWithPunctuation" class="ReverseStringTest" classname="ReverseStringTest" file="/solution/ReverseStringTest.php" line="28" assertions="1" time="0.000048"/>
        <testcase name="testPalindrome" class="ReverseStringTest" classname="ReverseStringTest" file="/solution/ReverseStringTest.php" line="33" assertions="1" time="0.000048"/>
        <testcase name="testEvenSizedWord" class="ReverseStringTest" classname="ReverseStringTest" file="/solution/ReverseStringTest.php" line="38" assertions="1" time="0.000063"/>
      </testsuite>
    </testsuites>
  `
}
