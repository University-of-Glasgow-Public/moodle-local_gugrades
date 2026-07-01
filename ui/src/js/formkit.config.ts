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
        input: 'w-full px-3 py-2 border border-brand-dark-blue/20 rounded-md focus:outline-none focus:border-brand-dark-blue focus:ring-1 focus:ring-brand-dark-blue',
        label: '$reset text-sm font-medium text-brand-dark-purple mb-1 block',
        legend: '$reset text-sm font-medium text-brand-dark-purple mb-1 block',
        help: 'text-xs text-brand-dark-blue/60 mt-1',
        messages: 'list-none pl-0 mt-1',
        message: 'text-sm text-brand-dark-red', 
      },
      form: {
        // form: "mt-5 mx-auto p-5 border rounded"
      },
      range: {
        input: '$reset w-full accent-brand-dark-blue',
      },
      submit: {
        outer: '$reset mt-3',
        input: '$reset px-4 py-2 bg-brand-dark-blue text-white font-medium rounded-md hover:bg-brand-dark-purple transition-colors cursor-pointer'
      },
      number: {
        input: '$reset w-full px-3 py-2 border border-brand-dark-blue/20 rounded-md focus:border-brand-dark-blue focus:ring-1 focus:ring-brand-dark-blue'
      },
      checkbox: {
        // Layout wrapper for multi-option groups
        outer: '$reset my-3 flex flex-col gap-2.5',
        // Structure for a single checkbox layout context
        wrapper: '$reset flex items-start gap-3 cursor-pointer',
        // Strips "block" layout entirely to ensure the label locks side-by-side with the square
        label: '$reset text-sm font-medium text-brand-dark-purple select-none cursor-pointer mt-px',
        // Strips global w-full sizing rules and protects structural integrity from shrinking
        input: '$reset h-4 w-4 shrink-0 rounded border-brand-dark-blue/20 text-brand-dark-blue focus:ring-brand-dark-blue',
        // Handles multi-option configurations (avoids text wrapping under the checkbox control)
        options: '$reset flex flex-col gap-2.5 list-none pl-0',
        option: '$reset flex items-start gap-3',
        // FormKit inner container that keeps layout inline
        inner: '$reset inline-flex items-center shrink-0'
      },
      radio: {
        outer: '$reset my-3',
        legend: '$reset mb-1.5 font-medium text-brand-dark-purple',
        // Individual wrapping option container for radio alignment
        wrapper: '$reset flex items-start gap-3 cursor-pointer',
        label: '$reset text-sm text-brand-dark-purple/90 font-medium cursor-pointer select-none mt-px',
        input: '$reset h-4 w-4 shrink-0 border-brand-dark-blue/20 text-brand-dark-blue focus:ring-brand-dark-blue',
        options: '$reset flex flex-col gap-2.5 list-none pl-0',
        option: '$reset flex items-start gap-3',
        inner: '$reset inline-flex items-center shrink-0'
      },
      select: {
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
