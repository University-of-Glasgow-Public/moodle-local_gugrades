declare global {
  interface Window {
      GU: GUType;
  }
}

interface GUType {
    courseid: number,
    fetchMany: CallableFunction,
}

export const moodleFetch = (methodname: string, args: Record<string, any>, async=true, loginrequired=true ): Promise<object> => {

    const GU = window.GU;
    const fetchMany = GU.fetchMany;
    const courseid = GU.courseid;

    args['courseid'] = courseid;

    return fetchMany([{
        methodname: methodname,
        args: args
    }], async, loginrequired)[0];
}