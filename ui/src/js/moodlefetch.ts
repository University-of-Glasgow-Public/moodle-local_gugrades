/**
 * This proxies ajax calls through service.php in mygrades directory.
 * This is so we can use Moodle's web services without having to load all
 * of Moodle's javascript stuff that we don't need.
 */

import ky from 'ky';

// Define the expected response types
interface MoodleException {
  message: string;
  errorcode: string;
  backtrace: string;
  link: string;
  moreinfourl: string;
  debuginfo: string;
}

interface MoodleErrorResponse {
  error: true;
  exception: MoodleException;
}

interface MoodleSuccessResponse {
  success: true;
  data: any;
}

type MoodleResponse = MoodleErrorResponse | MoodleSuccessResponse;

export const moodleFetch = async (
    methodname: string,
    args: Record<string, any>,
    async = true,
    loginrequired = true
): Promise<object> => {

  const pluginBase = new URL('../../', window.location.href).pathname;
  const courseidParam = new URLSearchParams(window.location.search).get('courseid');
  const courseid = courseidParam ? Number(courseidParam) : null;

  const siteBase = new URL('../../../../', window.location.href).pathname;

  const api = ky.create({
    prefix: `${pluginBase}ajax.php`,
    hooks: {
        afterResponse: [
            ({response}) => {
                if (response.status == 401) {
                    window.location.href = `${siteBase}course/view.php?id=${courseid}`;
                }
            }
        ]
      }
  });

  args.courseid = courseid;

  // Note that we don't have a timeout and we don't throw
  // (non - 2xx) errors. We check and handle error conditions
  // ourselves to get the Moodle exception data for display.
  const response = await api.post('', {
    json: {
      methodname,
      args,
      async,
      loginrequired
    },
    timeout: false,
    throwHttpErrors: false,
  }).json<MoodleResponse>();

  if ('error' in response && response.error) {
      throw response.exception;
  }

  return response as object;
};