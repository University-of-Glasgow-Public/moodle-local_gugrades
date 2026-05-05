/**
 * Styling config for FormKit
 * See,  https://formkit.com/essentials/styling
 */

import { generateClasses } from '@formkit/themes'

const config = {
  config: {
    classes: generateClasses({
      global: { // classes
        outer: '$reset my-1',
        input: 'fieldset',
        label: '$reset fieldset-legend',
        legend: '$reset fieldset-legend',
        help: 'text-sm',
        messages: 'list-unstyled mt-1',
        message: 'text-error',
      },
      form: {
        //form: "mt-5 mx-auto p-5 border rounded"
      },
      range: {
        input: '$reset form-range',
      },
      submit: {
        outer: '$reset mt-3',
        input: '$reset btn btn-primary'
      },
      number: {
        input: '$reset input'
      },
      checkbox: {
        outer: '$reset fieldset',
        input: '$reset checkbox',
        wrapper: '$reset label',
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
        input: '$reset select',
      },
      text: {
        input: '$reset input',
      },
      textarea: {
        input: '$reset textarea',
      }
    })
  }
}

export default config