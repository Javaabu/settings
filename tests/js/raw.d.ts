// stencil's renderer imports its stylesheet with Vite's ?raw suffix
declare module "*?raw" {
  const content: string;
  export default content;
}
