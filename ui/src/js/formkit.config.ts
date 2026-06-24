/**
 * Styling config for FormKit
 * See, https://formkit.com/essentials/styling
 */

import { generateClasses } from '@formkit/themes'

const config = {
  config: {
    classes: generateClasses({
      global: {
        outer: '$reset my-1 font-sans',
        // Replacing DaisyUI fieldset/input with pure Tailwind and brand borders
        input: 'w-full px-3 py-2 border border-brand-dark-blue/20 rounded-md focus:outline-none focus:border-brand-dark-blue focus:ring-1 focus:ring-brand-dark-blue',
        label: '$reset text-sm font-medium text-brand-dark-purple mb-1 block',
        legend: '$reset text-sm font-medium text-brand-dark-purple mb-1 block',
        help: 'text-xs text-brand-dark-blue/60 mt-1',
        messages: 'list-none pl-0 mt-1',
        message: 'text-sm text-brand-dark-red', // Uses your brand dark red for errors
      },
      form: {
        // form: "mt-5 mx-auto p-5 border rounded"
      },
      range: {
        input: '$reset w-full accent-brand-dark-blue',
      },
      submit: {
        outer: '$reset mt-3',
        // Custom button styling using your brand colors instead of `.btn-primary`
        input: '$reset px-4 py-2 bg-brand-dark-blue text-white font-medium rounded-md hover:bg-brand-dark-purple transition-colors cursor-pointer'
      },
      number: {
        input: '$reset w-full px-3 py-2 border border-brand-dark-blue/20 rounded-md focus:border-brand-dark-blue focus:ring-1 focus:ring-brand-dark-blue'
      },
      checkbox: {
        outer: '$reset my-2',
        input: '$reset rounded border-brand-dark-blue/20 text-brand-dark-blue focus:ring-brand-dark-blue',
        wrapper: '$reset flex items-center gap-2 cursor-pointer',
      },
      radio: {
        legend: '$reset mb-1 font-medium text-brand-dark-purple',
        label: '$reset text-sm text-brand-dark-purple/90 cursor-pointer',
        outer: '$reset my-2',
        input: '$reset border-brand-dark-blue/20 text-brand-dark-blue focus:ring-brand-dark-blue',
        options: '$reset flex flex-wrap gap-4 list-none pl-0',
        option: '$reset flex items-center gap-2'
      },
      select: {
        // FIXED: Added pr-10 for arrow clearance, truncate to stop bleeding text, and fixed width boundary
        input: '$reset w-full max-w-full pl-3 pr-10 py-2 border border-brand-dark-blue/20 rounded-md focus:border-brand-dark-blue focus:ring-1 focus:ring-brand-dark-blue bg-no-repeat truncate appearance-none',
      },
      text: {
        input: '$reset w-full px-3 py-2 border border-brand-dark-blue/20 rounded-md focus:border-brand-dark-blue focus:ring-1 focus:ring-brand-dark-blue',
      },
      textarea: {
        input: '$reset w-full px-3 py-2 border border-brand-dark-blue/20 rounded-md focus:border-brand-dark-blue focus:ring-1 focus:ring-brand-dark-blue min-h-[80px]',
      }
    })
  }
}

export default config
