/**
 * Styling config for FormKit
 * See,  https://formkit.com/essentials/styling
 */

import { generateClasses } from '@formkit/themes'

const config = {
  config: {
    classes: generateClasses({
      global: { // classes
        outer: '$reset tw:my-1',
        input: 'tw:fieldset',
        label: '$reset tw:fieldset-legend',
        legend: '$reset tw:fieldset-legend',
        help: 'tw:text-sm',
        messages: 'list-unstyled mt-1',
        message: 'text-danger',
      },
      form: {
        //form: "mt-5 mx-auto p-5 border rounded"
      },
      range: {
        input: '$reset form-range',
      },
      submit: {
        outer: '$reset tw:mt-3',
        input: '$reset tw:btn tw:btn-primary'
      },
      checkbox: {
        outer: '$reset tw:fieldset',
        input: '$reset tw:checkbox',
        wrapper: '$reset tw:label',
      },
      radio: {
        legend: '$reset mb-0 font-weight-bold',
        label: '$reset ml-1',
        outer: '$reset form-check form-check-inline',
        input: '$reset form-check-input',
        options: '$reset list-unstyled mb-0',
        option: '$reset pr-3'
      },
      select: {
        input: '$reset tw:select',
      },
      text: {
        input: '$reset tw:input',
      },
      textarea: {
        input: '$reset tw:textarea',
      }
    })
  }
}

export default config