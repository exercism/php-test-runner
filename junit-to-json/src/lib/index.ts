import {load} from 'cheerio'

type CheerioRoot = ReturnType<typeof load>
type CheerioSelection = ReturnType<CheerioRoot>

function isTestCases(
  testcases: TestCase[] | undefined
): testcases is TestCase[] {
  return testcases !== undefined
}

export function processXmlResult(xmlContent: Buffer): ExercismTestRunnerResult {
  const $ = load(xmlContent, {xmlMode: true})
  const parsed = parseTestSuites($)

  if (parsed.length === 0) {
    return {
      version: 2,
      tests: [],
      status: "error",
      message: "Unit test run did not produce any results. Did the tests die?"
    } as ExercismTestRunnerError
  }

  return {
    version: 2,
    tests: parsed
      .map((suite) => suite.testCases)
      .filter(isTestCases)
      .flat(),
    status: parsed.reduce<'pass' | 'fail'>((status, testSuite) => {
      return testSuite.errors !== 0 || testSuite.failures !== 0
        ? 'fail'
        : status
    }, 'pass'),
  };
}

export function parseTestSuites($: CheerioRoot): TestSuite[] {
  return $('testsuites')
    .children()
    .toArray()
    .map((testSuiteEl) => {
      const selectedTestSuite = $(testSuiteEl)
      const testSuite = parseTestSuite(selectedTestSuite)

      testSuite.testCases = selectedTestSuite
        .children()
        .toArray()
        .map((testCase) => parseTestCase($(testCase)))

      return testSuite
    })
}

export function parseTestSuite(testSuiteEl: CheerioSelection): TestSuite {
  return {
    tests: Number(testSuiteEl.attr('tests')),
    assertions: Number(testSuiteEl.attr('assertions')),
    errors: Number(testSuiteEl.attr('errors')),
    warnings: Number(testSuiteEl.attr('warnings')),
    failures: Number(testSuiteEl.attr('failures')),
    skipped: Number(testSuiteEl.attr('skipped')),
  }
}

export function parseTestCase(testCaseEl: CheerioSelection): TestCase {
  const name = testCaseEl.attr('name') ?? null
  const failure = testCaseEl.find('failure')?.text() ?? null
  const error = testCaseEl.find('error')?.text() ?? null
  const systemOut = testCaseEl.find('system-out')?.text() ?? null

  if (!name) {
    throw new Error('Test name not found. Check test suite test names')
  }

  if (error) {
    return {
      name,
      status: 'error',
      message: error,
      output: systemOut,
    }
  }

  if (failure) {
    return {
      name,
      status: 'fail',
      message: failure,
      output: systemOut,
    }
  }

  return {
    name,
    status: 'pass',
    output: systemOut,
  }
}
