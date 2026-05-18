export type FilterOptions = {
  field: string,
  comparison: 'between',
  criteria: [number, number],
} | {
  field: string,
  comparison: '=' | '!=',
  criteria: number | string,
} | {
  field: string,
  comparison: '>' | '>=' | '<' | '<=',
  criteria: number,
} | {
  field: string,
  comparison: (value: any, criteria: string) => boolean,
  criteria: string,
} | {
  field: string
  comparison: 'in'
  criteria: number[] | string[]
}