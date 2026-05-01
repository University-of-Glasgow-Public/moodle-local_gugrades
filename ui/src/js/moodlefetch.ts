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

  const api = ky.create({
    prefix: `${pluginBase}service.php`
  });

  args.courseid = courseid;

  const response = await api.post('', {
    json: {
      methodname,
      args,
      async,
      loginrequired
    }
  }).json();

  return response as object;
};