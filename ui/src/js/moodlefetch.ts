/**
 * This proxies ajax calls through service.php in mygrades directory.
 * This is so we can use Moodle's web services without having to load all
 * of Moodle's javascript stuff that we don't need.
 */

import ky from 'ky';

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

  const response = await api.post('', {
    json: {
      methodname,
      args,
      async,
      loginrequired
    },
    timeout: false
  }).json();

  return response as object;
};