declare global {
  interface Window {
      GU: GUType;
  }
}

interface GUType {
    courseid: BigInteger,
    fetchMany: CallableFunction,
}

export const moodleFetch = (methodname: string, args: object): Promise<object> => {

    const GU = window.GU;
    const fetchMany = GU.fetchMany;
    const courseid = GU.courseid;

    args['courseid'] = courseid;

    return fetchMany([{
        methodname: methodname,
        args: args
    }])[0];
}